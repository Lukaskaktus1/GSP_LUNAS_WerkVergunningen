<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($email === '' || $password === '' || $passwordConfirm === '') {
    setFlashMessage('error', 'Vul alle velden in.');
    redirect('register.php');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    setFlashMessage('error', 'Voer een geldig e-mailadres in.');
    redirect('register.php');
}

if (strlen($password) < 8) {
    setFlashMessage('error', 'Het wachtwoord moet minstens 8 tekens lang zijn.');
    redirect('register.php');
}

if ($password !== $passwordConfirm) {
    setFlashMessage('error', 'De wachtwoorden komen niet overeen.');
    redirect('register.php');
}

try {
    $pdo = getDbConnection();

    $checkStmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $checkStmt->execute([
        'email' => $email
    ]);

    if ($checkStmt->fetch()) {
        setFlashMessage('error', 'Er bestaat al een account met dit e-mailadres.');
        redirect('register.php');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("
        INSERT INTO users (email, wachtwoord_hash, rol, actief)
        VALUES (:email, :wachtwoord_hash, :rol, :actief)
    ");

    $insertStmt->execute([
        'email' => $email,
        'wachtwoord_hash' => $passwordHash,
        'rol' => 'leerling',
        'actief' => 1,
    ]);

    setFlashMessage('success', 'Account succesvol aangemaakt. U kunt nu inloggen.');
    redirect('index.php');

} catch (Throwable $exception) {
    error_log('Register failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Account aanmaken is momenteel niet beschikbaar.');
    redirect('register.php');
}