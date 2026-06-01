<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$voornaam = trim((string) ($_POST['voornaam'] ?? ''));
$achternaam = trim((string) ($_POST['achternaam'] ?? $_POST['naam'] ?? ''));
$telefoon = trim((string) ($_POST['telefoon'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($voornaam === '' || $achternaam === '' || $telefoon === '' || $email === '' || $password === '' || $passwordConfirm === '') {
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

    if (!databaseTableExists($pdo, 'user_profiel')) {
        setFlashMessage('error', 'De profielgegevens-tabel ontbreekt nog in de database.');
        redirect('register.php');
    }

    $lastNameColumn = databaseColumnExists($pdo, 'user_profiel', 'achternaam') ? 'achternaam' : 'naam';
    $requiredProfileColumns = ['user_id', 'voornaam', $lastNameColumn, 'telefoon'];

    foreach ($requiredProfileColumns as $column) {
        if (!databaseColumnExists($pdo, 'user_profiel', $column)) {
            setFlashMessage('error', 'De profielgegevens-tabel heeft niet alle nodige velden.');
            redirect('register.php');
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    try {
        $insertUserStmt = $pdo->prepare('
            INSERT INTO users (email, wachtwoord_hash, rol, actief)
            VALUES (:email, :wachtwoord_hash, :rol, :actief)
        ');

        $insertUserStmt->execute([
            'email' => $email,
            'wachtwoord_hash' => $passwordHash,
            'rol' => 'leerling',
            'actief' => 1,
        ]);

        $userId = (int) $pdo->lastInsertId();

        $profileColumns = ['user_id', 'voornaam', $lastNameColumn, 'telefoon'];
        $profilePlaceholders = array_map(static fn (string $column): string => ':' . $column, $profileColumns);

        $insertProfileStmt = $pdo->prepare(sprintf(
            'INSERT INTO user_profiel (%s) VALUES (%s)',
            implode(', ', $profileColumns),
            implode(', ', $profilePlaceholders)
        ));

        $insertProfileStmt->execute([
            'user_id' => $userId,
            'voornaam' => $voornaam,
            $lastNameColumn => $achternaam,
            'telefoon' => $telefoon,
        ]);

        $pdo->commit();
    } catch (Throwable $transactionException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $transactionException;
    }

    $message = "Hallo {$voornaam},\n\n"
        . "Je account voor het Werkvergunning Portaal is aangemaakt.\n"
        . "Je kan nu inloggen met dit e-mailadres: {$email}\n\n"
        . "Met vriendelijke groeten,\nGTI Beveren";

    sendPortalMail($email, 'Bevestiging account Werkvergunning Portaal', $message);

    setFlashMessage('success', 'Account succesvol aangemaakt. Er is een bevestigingsmail verstuurd en u kunt nu inloggen.');
    redirect('index.php');

} catch (Throwable $exception) {
    error_log('Register failed: ' . $exception->getMessage());
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        setFlashMessage('error', 'Er bestaat al een account met dit e-mailadres.');
        redirect('register.php');
    }

    setFlashMessage('error', 'Account aanmaken is momenteel niet beschikbaar.');
    redirect('register.php');
}
