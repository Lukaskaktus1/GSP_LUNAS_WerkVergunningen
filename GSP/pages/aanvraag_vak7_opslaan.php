<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mijn_aanvragen.php');
}

$vergunningId = filter_input(INPUT_POST, 'vergunning_id', FILTER_VALIDATE_INT);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$vergunningId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $vergunningId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag) || !magVergunningVak7($aanvraag, $userId, $pdo)) {
        setFlashMessage('error', 'Vak VII is nog niet beschikbaar.');
        redirect('mijn_aanvragen.php');
    }

    $payload = [
        'werkplek_proper' => trim((string) ($_POST['werkplek_proper'] ?? '')),
        'taak_afgerond' => trim((string) ($_POST['taak_afgerond'] ?? '')),
        'opmerking' => trim((string) ($_POST['opmerking'] ?? '')),
        'afgesloten_door' => currentUserDisplayName(),
        'afgesloten_op' => date('Y-m-d H:i:s'),
    ];

    if ($payload['werkplek_proper'] === '' || $payload['taak_afgerond'] === '') {
        setFlashMessage('error', 'Beantwoord alle verplichte Vak VII-vragen.');
        redirect('aanvraag_vak7.php?id=' . $vergunningId);
    }

    $pdo->beginTransaction();

    if (databaseTableExists($pdo, 'werkvergunning_vak7_afsluiting')) {
        $existsStmt = $pdo->prepare('SELECT vergunning_id FROM werkvergunning_vak7_afsluiting WHERE vergunning_id = :vergunning_id LIMIT 1');
        $existsStmt->execute(['vergunning_id' => $vergunningId]);

        if ($existsStmt->fetch()) {
            $save = $pdo->prepare('
                UPDATE werkvergunning_vak7_afsluiting
                SET payload_json = :payload_json, is_volledig = 1
                WHERE vergunning_id = :vergunning_id
            ');
        } else {
            $save = $pdo->prepare('
                INSERT INTO werkvergunning_vak7_afsluiting (vergunning_id, payload_json, is_volledig)
                VALUES (:vergunning_id, :payload_json, 1)
            ');
        }

        $save->execute([
            'vergunning_id' => $vergunningId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    $updateParts = ['status = :status'];
    $params = ['status' => 'afgerond', 'id' => $vergunningId];

    if (databaseColumnExists($pdo, 'werkvergunning', 'updated_at')) {
        $updateParts[] = 'updated_at = NOW()';
    }

    $statusStmt = $pdo->prepare('UPDATE werkvergunning SET ' . implode(', ', $updateParts) . ' WHERE id = :id');
    $statusStmt->execute($params);

    $pdo->commit();

    setFlashMessage('success', 'Werkvergunning is afgerond via Vak VII.');
    redirect('aanvraag_bekijken.php?id=' . $vergunningId);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('aanvraag_vak7_opslaan failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Vak VII opslaan is mislukt.');
    redirect('aanvraag_vak7.php?id=' . (int) $vergunningId);
}
