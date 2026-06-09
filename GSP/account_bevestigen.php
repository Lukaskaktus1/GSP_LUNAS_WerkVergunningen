<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    setFlashMessage('error', 'Ongeldige bevestigingslink.');
    redirect('index.php');
}

try {
    $pdo = getDbConnection();

    if (confirmAccountByToken($pdo, $token)) {
        setFlashMessage('success', 'Uw account is bevestigd. U kunt nu inloggen.');
        redirect('index.php');
    }

    setFlashMessage('error', 'Deze bevestigingslink is ongeldig of vervallen.');
    redirect('index.php');
} catch (Throwable $exception) {
    error_log('Account bevestigen failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Account bevestigen is momenteel niet beschikbaar.');
    redirect('index.php');
}
