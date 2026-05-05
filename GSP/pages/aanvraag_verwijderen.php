<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mijn_aanvragen.php');
}

$aanvraagId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$aanvraagId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = (string) ($_SESSION['rol'] ?? '');

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT id, eigenaar_user_id, status
        FROM werkvergunning
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        'id' => $aanvraagId,
    ]);

    $aanvraag = $stmt->fetch();

    if (!$aanvraag) {
        setFlashMessage('error', 'Aanvraag niet gevonden.');
        redirect('mijn_aanvragen.php');
    }

    $isEigenaar = (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId;
    $isAdmin = $role === 'admin';

    if (!$isEigenaar && !$isAdmin) {
        setFlashMessage('error', 'U mag deze aanvraag niet verwijderen.');
        redirect('mijn_aanvragen.php');
    }

    if (in_array((string) $aanvraag['status'], ['goedgekeurd', 'gesloten'], true)) {
        setFlashMessage('error', 'Goedgekeurde of gesloten aanvragen kunnen niet verwijderd worden.');
        redirect('mijn_aanvragen.php');
    }

    $deleteStmt = $pdo->prepare("
        DELETE FROM werkvergunning
        WHERE id = :id
    ");

    $deleteStmt->execute([
        'id' => $aanvraagId,
    ]);

    setFlashMessage('success', 'Aanvraag succesvol verwijderd.');
    redirect('mijn_aanvragen.php');

} catch (Throwable $exception) {
    error_log('Aanvraag verwijderen failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Aanvraag verwijderen is mislukt.');
    redirect('mijn_aanvragen.php');
}