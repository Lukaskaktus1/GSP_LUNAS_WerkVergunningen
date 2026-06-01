<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = trim((string) ($_POST['password'] ?? ''));

if ($email === '' || $password === '') {
    setFlashMessage('error', 'Vul uw e-mailadres en wachtwoord in.');
    redirect('index.php');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    setFlashMessage('error', 'Voer een geldig e-mailadres in.');
    redirect('index.php');
}

try {
    $pdo = getDbConnection();

    $profileSelect = '';
    $profileJoin = '';

    if (databaseTableExists($pdo, 'user_profiel') && databaseColumnExists($pdo, 'user_profiel', 'user_id')) {
        $lastNameColumn = databaseColumnExists($pdo, 'user_profiel', 'achternaam') ? 'achternaam' : 'naam';
        if (
            databaseColumnExists($pdo, 'user_profiel', 'voornaam')
            && databaseColumnExists($pdo, 'user_profiel', $lastNameColumn)
            && databaseColumnExists($pdo, 'user_profiel', 'telefoon')
        ) {
            $profileSelect = ", p.voornaam AS voornaam, p.{$lastNameColumn} AS naam, p.telefoon AS telefoon";
            $profileJoin = ' LEFT JOIN user_profiel p ON p.user_id = u.id';
        }
    }

    $statement = $pdo->prepare("
        SELECT u.id, u.email, u.rol, u.wachtwoord_hash, u.actief{$profileSelect}
        FROM users u
        {$profileJoin}
        WHERE u.email = :email
        LIMIT 1
    ");
    $statement->execute(['email' => mb_strtolower($email)]);
    $user = $statement->fetch();

    $isValidUser = is_array($user)
        && (int) ($user['actief'] ?? 0) === 1
        && isset($user['wachtwoord_hash'])
        && password_verify($password, (string) $user['wachtwoord_hash']);

    if (!$isValidUser) {
        setFlashMessage('error', 'Ongeldige inloggegevens.');
        redirect('index.php');
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['email'] = (string) $user['email'];
    $_SESSION['rol'] = (string) $user['rol'];
    $_SESSION['voornaam'] = (string) ($user['voornaam'] ?? '');
    $_SESSION['naam'] = (string) ($user['naam'] ?? '');
    $_SESSION['telefoon'] = (string) ($user['telefoon'] ?? '');

    $historyStatement = $pdo->prepare(
        'INSERT INTO login_history (user_id, email, rol, ip_adres, user_agent)
         VALUES (:user_id, :email, :rol, :ip_adres, :user_agent)'
    );
    $historyStatement->execute([
        'user_id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'rol' => (string) $user['rol'],
        'ip_adres' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    $redirects = [
    'leerling' => 'pages/overzicht_leerling.php',
    'leerkracht' => 'pages/overzicht_leerkracht.php',
    'ta' => 'pages/overzicht_ta.php',
    'directeur' => 'pages/overzicht_directeur.php',
    'admin' => 'pages/overzicht_admin.php',
    ];
    
    redirect($redirects[$user['rol']] ?? 'pages/overzicht_leerling.php');
} catch (Throwable $exception) {
    error_log('Login failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Inloggen is momenteel niet beschikbaar.');
    redirect('index.php');
}
