<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mijn_aanvragen.php');
}

$aanvraagId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$actie = trim((string) ($_POST['actie'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$aanvraagId || !in_array($actie, ['aanpassen', 'verwijderen'], true)) {
    setFlashMessage('error', 'Ongeldig verzoek.');
    redirect('mijn_aanvragen.php');
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $aanvraagId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag) || (int) ($aanvraag['eigenaar_user_id'] ?? 0) !== $userId) {
        setFlashMessage('error', 'U kunt alleen een verzoek sturen voor uw eigen aanvraag.');
        redirect('mijn_aanvragen.php');
    }

    $pdo->beginTransaction();

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS aanvraag_actie_verzoek (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vergunning_id INT NOT NULL,
            aanvrager_user_id INT NOT NULL,
            actie VARCHAR(40) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_aanvraag_actie_verzoek_vergunning (vergunning_id),
            INDEX idx_aanvraag_actie_verzoek_status (status)
        )
    SQL);

    $insert = $pdo->prepare('
        INSERT INTO aanvraag_actie_verzoek (vergunning_id, aanvrager_user_id, actie)
        VALUES (:vergunning_id, :aanvrager_user_id, :actie)
    ');
    $insert->execute([
        'vergunning_id' => $aanvraagId,
        'aanvrager_user_id' => $userId,
        'actie' => $actie,
    ]);

    if (
        databaseTableExists($pdo, 'notificatie')
        && databaseColumnExists($pdo, 'notificatie', 'user_id')
        && databaseColumnExists($pdo, 'notificatie', 'type')
        && databaseColumnExists($pdo, 'notificatie', 'boodschap')
    ) {
        $ontvangersStmt = $pdo->query("
            SELECT id
            FROM users
            WHERE rol IN ('leerkracht', 'ta', 'admin')
              AND actief = 1
        ");
        $ontvangers = array_map('intval', $ontvangersStmt->fetchAll(PDO::FETCH_COLUMN));
        $notificatieStmt = $pdo->prepare('
            INSERT INTO notificatie (user_id, type, boodschap)
            VALUES (:user_id, :type, :boodschap)
        ');
        $boodschap = currentUserDisplayName() . ' vraagt om werkvergunning '
            . (string) ($aanvraag['vergunning_nummer'] ?? ('#' . $aanvraagId))
            . ' te ' . ($actie === 'aanpassen' ? 'mogen aanpassen.' : 'verwijderen.');

        foreach ($ontvangers as $ontvangerId) {
            $notificatieStmt->execute([
                'user_id' => $ontvangerId,
                'type' => 'aanvraag_verzoek',
                'boodschap' => $boodschap,
            ]);
        }
    }

    $pdo->commit();

    setFlashMessage('success', 'Uw verzoek werd doorgestuurd.');
    redirect('mijn_aanvragen.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('aanvraag_actie_aanvragen failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Het verzoek kon niet worden doorgestuurd.');
    redirect('mijn_aanvragen.php');
}
