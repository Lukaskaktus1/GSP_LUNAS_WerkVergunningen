<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$voornaam = trim((string) ($_POST['voornaam'] ?? ''));
$naam = trim((string) ($_POST['naam'] ?? ''));
$telefoon = trim((string) ($_POST['telefoon'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($voornaam === '' || $naam === '' || $telefoon === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    setFlashMessage('error', 'Vul alle velden in.');
    redirect('register.php');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    setFlashMessage('error', 'Voer een geldig e-mailadres in.');
    redirect('register.php');
}

if (!passwordMeetsPolicy($password)) {
    setFlashMessage('error', passwordPolicyMessage());
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

    $insertColumns = ['email', 'wachtwoord_hash', 'rol', 'actief'];
    $insertValues = [
        'email' => $email,
        'wachtwoord_hash' => $passwordHash,
        'rol' => 'leerling',
        'actief' => 1,
    ];

    $optionalValues = [
        'voornaam' => $voornaam,
        'naam' => $naam,
        'telefoon' => $telefoon,
        'bevestiging_token' => bin2hex(random_bytes(32)),
    ];

    foreach ($optionalValues as $column => $value) {
        if (databaseColumnExists($pdo, 'users', $column)) {
            $insertColumns[] = $column;
            $insertValues[$column] = $value;
        }
    }

    $placeholders = array_map(static fn (string $column): string => ':' . $column, $insertColumns);

    $insertStmt = $pdo->prepare(sprintf(
        'INSERT INTO users (%s) VALUES (%s)',
        implode(', ', $insertColumns),
        implode(', ', $placeholders)
    ));

    $insertStmt->execute($insertValues);

    $message = "Hallo {$voornaam},\n\n"
        . "Je account voor het Werkvergunning Portaal is aangemaakt.\n"
        . "Je kan nu inloggen met dit e-mailadres: {$email}\n\n"
        . "Met vriendelijke groeten,\nGTI Beveren";

    sendPortalMail($email, 'Bevestiging account Werkvergunning Portaal', $message);

    setFlashMessage('success', 'Account succesvol aangemaakt. Er is een bevestigingsmail verstuurd en u kunt nu inloggen.');
    redirect('index.php');

} catch (Throwable $exception) {
    error_log('Register failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Account aanmaken is momenteel niet beschikbaar.');
    redirect('register.php');
}
