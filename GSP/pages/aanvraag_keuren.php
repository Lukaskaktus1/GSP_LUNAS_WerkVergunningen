<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['directeur', 'ta', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('keuringen.php');
}

$aanvraagId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$actie = trim((string) ($_POST['actie'] ?? ''));

if (!$aanvraagId || !in_array($actie, ['goedkeuren', 'afkeuren'], true)) {
    setFlashMessage('error', 'Ongeldige keuring.');
    redirect('keuringen.php');
}

$nieuweStatus = $actie === 'goedkeuren' ? 'goedgekeurd' : 'afgekeurd';

try {
    $pdo = getDbConnection();

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, vergunning_nummer, eigenaar_user_id, eigenaar_email, status
        FROM werkvergunning
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        'id' => $aanvraagId,
    ]);

    $aanvraag = $stmt->fetch();

    if (!$aanvraag) {
        $pdo->rollBack();
        setFlashMessage('error', 'Aanvraag niet gevonden.');
        redirect('keuringen.php');
    }

    if (!in_array((string) $aanvraag['status'], ['ingediend', 'in_beoordeling'], true)) {
        $pdo->rollBack();
        setFlashMessage('error', 'Deze aanvraag kan niet meer gekeurd worden.');
        redirect('keuringen.php');
    }

    $updateStmt = $pdo->prepare("
        UPDATE werkvergunning
        SET status = :status
        WHERE id = :id
    ");

    $updateStmt->execute([
        'status' => $nieuweStatus,
        'id' => $aanvraagId,
    ]);

    if (!empty($aanvraag['eigenaar_user_id'])) {
        $boodschap = $actie === 'goedkeuren'
            ? 'Uw werkvergunning ' . $aanvraag['vergunning_nummer'] . ' is goedgekeurd.'
            : 'Uw werkvergunning ' . $aanvraag['vergunning_nummer'] . ' is afgekeurd.';

        $notificatieStmt = $pdo->prepare("
            INSERT INTO notificatie (user_id, type, boodschap)
            VALUES (:user_id, :type, :boodschap)
        ");

        $notificatieStmt->execute([
            'user_id' => (int) $aanvraag['eigenaar_user_id'],
            'type' => 'keuring',
            'boodschap' => $boodschap,
        ]);
    }

    $pdo->commit();

    setFlashMessage('success', 'De aanvraag werd succesvol ' . ($actie === 'goedkeuren' ? 'goedgekeurd.' : 'afgekeurd.'));
    redirect('keuringen.php');

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Aanvraag keuren failed: ' . $exception->getMessage());
    setFlashMessage('error', 'De aanvraag keuren is mislukt.');
    redirect('keuringen.php');
}