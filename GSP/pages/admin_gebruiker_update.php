<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_gebruikers.php');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$rol = trim((string) ($_POST['rol'] ?? ''));
$actief = filter_input(INPUT_POST, 'actief', FILTER_VALIDATE_INT);

$allowedRoles = [
    'leerling',
    'leerkracht',
    'ta',
    'directeur',
    'admin',
];

if (!$userId || !in_array($rol, $allowedRoles, true) || !in_array($actief, [0, 1], true)) {
    setFlashMessage('error', 'Ongeldige gegevens.');
    redirect('admin_gebruikers.php');
}

// Voorkom dat de admin zichzelf per ongeluk uitschakelt
if ($userId === (int) $_SESSION['user_id'] && $actief === 0) {
    setFlashMessage('error', 'Je kan je eigen account niet uitschakelen.');
    redirect('admin_gebruikers.php');
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        UPDATE users
        SET rol = :rol,
            actief = :actief
        WHERE id = :id
    ");

    $stmt->execute([
        'rol' => $rol,
        'actief' => $actief,
        'id' => $userId,
    ]);

    setFlashMessage('success', 'Gebruiker succesvol aangepast.');
    redirect('admin_gebruikers.php');

} catch (Throwable $exception) {
    error_log('Admin user update failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Gebruiker aanpassen is mislukt.');
    redirect('admin_gebruikers.php');
}