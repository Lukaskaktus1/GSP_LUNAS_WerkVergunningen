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
$rol = trim((string) ($_POST['rol'] ?? 'leerling'));
$klas = trim((string) ($_POST['klas'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
$leerkrachtKlassen = is_array($_POST['leerkracht_klas'] ?? null) ? $_POST['leerkracht_klas'] : [];
$leerkrachtVakken = is_array($_POST['leerkracht_vak'] ?? null) ? $_POST['leerkracht_vak'] : [];
$toegelatenKlassen = array_keys(gspKlassen());
$toegelatenVakken = array_keys(gspAfdelingen());
$leerkrachtKlasVakken = [];

if (!in_array($rol, ['leerling', 'leerkracht'], true)) {
    setFlashMessage('error', 'Kies of u registreert als leerling of leerkracht.');
    redirect('register.php');
}

if ($voornaam === '' || $achternaam === '' || $telefoon === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    setFlashMessage('error', 'Vul alle velden in.');
    redirect('register.php');
}

if ($rol === 'leerling' && $klas === '') {
    setFlashMessage('error', 'Vul uw klas in.');
    redirect('register.php');
}

if ($rol === 'leerkracht') {
    foreach ($leerkrachtKlassen as $index => $teacherClass) {
        $teacherClass = trim((string) $teacherClass);
        $teacherSubject = trim((string) ($leerkrachtVakken[$index] ?? ''));

        if ($teacherClass === '' && $teacherSubject === '') {
            continue;
        }

        if ($teacherClass === '' || $teacherSubject === ''
            || !in_array($teacherClass, $toegelatenKlassen, true)
            || !in_array($teacherSubject, $toegelatenVakken, true)
        ) {
            setFlashMessage('error', 'Kies een geldige klas en een geldig vak uit de lijst.');
            redirect('register.php');
        }

        $leerkrachtKlasVakken[$teacherClass . '|' . $teacherSubject] = [
            'klas' => $teacherClass,
            'vak' => $teacherSubject,
        ];
    }

    $leerkrachtKlasVakken = array_values($leerkrachtKlasVakken);

    if ($leerkrachtKlasVakken === []) {
        setFlashMessage('error', 'Vul minstens een klas en vak in als leerkracht.');
        redirect('register.php');
    }
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
            'rol' => $rol,
            'actief' => 0,
        ]);

        $userId = (int) $pdo->lastInsertId();

        $profileColumns = ['user_id', 'voornaam', $lastNameColumn, 'telefoon'];

        if (databaseColumnExists($pdo, 'user_profiel', 'klas')) {
            $profileColumns[] = 'klas';
        }
        if (databaseColumnExists($pdo, 'user_profiel', 'vak')) {
            $profileColumns[] = 'vak';
        }

        $profilePlaceholders = array_map(static fn (string $column): string => ':' . $column, $profileColumns);

        $insertProfileStmt = $pdo->prepare(sprintf(
            'INSERT INTO user_profiel (%s) VALUES (%s)',
            implode(', ', $profileColumns),
            implode(', ', $profilePlaceholders)
        ));

        $profileParams = [
            'user_id' => $userId,
            'voornaam' => $voornaam,
            $lastNameColumn => $achternaam,
            'telefoon' => $telefoon,
        ];

        if (in_array('klas', $profileColumns, true)) {
            $profileParams['klas'] = $rol === 'leerling' ? $klas : $leerkrachtKlasVakken[0]['klas'];
        }
        if (in_array('vak', $profileColumns, true)) {
            $profileParams['vak'] = $rol === 'leerkracht' ? $leerkrachtKlasVakken[0]['vak'] : null;
        }

        $insertProfileStmt->execute($profileParams);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_klas_vak (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                klas VARCHAR(60) NOT NULL,
                vak VARCHAR(80) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_klas_vak_user (user_id),
                INDEX idx_user_klas_vak_klas (klas)
            )
        ");

        $insertClassStmt = $pdo->prepare('
            INSERT INTO user_klas_vak (user_id, klas, vak)
            VALUES (:user_id, :klas, :vak)
        ');

        if ($rol === 'leerling') {
            $insertClassStmt->execute([
                'user_id' => $userId,
                'klas' => $klas,
                'vak' => null,
            ]);
        } elseif ($rol === 'leerkracht') {
            foreach ($leerkrachtKlasVakken as $klasVak) {
                $insertClassStmt->execute([
                    'user_id' => $userId,
                    'klas' => $klasVak['klas'],
                    'vak' => $klasVak['vak'],
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $transactionException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $transactionException;
    }

    $confirmationToken = createAccountConfirmationToken($pdo, $userId);

    if ($confirmationToken === null) {
        setFlashMessage('error', 'Account aangemaakt, maar de bevestigingsmail kon niet worden voorbereid. Contacteer de beheerder.');
        redirect('index.php');
    }

    $confirmationLink = appBaseUrl() . '/account_bevestigen.php?token=' . urlencode($confirmationToken);

    $message = "Hallo {$voornaam},\n\n"
        . "Je account voor het Werkvergunning Portaal is aangemaakt, maar is nog niet actief.\n"
        . "Bevestig je e-mailadres via deze link:\n{$confirmationLink}\n\n"
        . "Deze link blijft 48 uur geldig.\n\n"
        . "Pas na bevestiging kan je inloggen met dit e-mailadres: {$email}\n\n"
        . "Met vriendelijke groeten,\nGTI Beveren";

    sendPortalMail($email, 'Bevestig uw account Werkvergunning Portaal', $message);

    setFlashMessage('success', 'Account aangemaakt. Controleer uw mailbox en klik op de bevestigingslink voordat u inlogt.');
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
