<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['leerkracht', 'ta', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_gebruikers.php');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$rol = trim((string) ($_POST['rol'] ?? ''));
$actief = filter_input(INPUT_POST, 'actief', FILTER_VALIDATE_INT);
$actie = trim((string) ($_POST['actie'] ?? 'opslaan'));

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

    if ((string) ($_SESSION['rol'] ?? '') === 'leerkracht') {
        $hasProfile = databaseTableExists($pdo, 'user_profiel');
        $hasProfileKlas = $hasProfile && databaseColumnExists($pdo, 'user_profiel', 'klas');
        $hasUserKlasVak = databaseTableExists($pdo, 'user_klas_vak');
        $profileJoin = $hasProfile ? 'LEFT JOIN user_profiel p ON p.user_id = u.id' : '';
        $classJoin = $hasUserKlasVak ? 'LEFT JOIN user_klas_vak ukv ON ukv.user_id = u.id' : '';
        $classExpression = $hasUserKlasVak && $hasProfileKlas
            ? "COALESCE(ukv.klas, p.klas, '')"
            : ($hasUserKlasVak ? "COALESCE(ukv.klas, '')" : ($hasProfileKlas ? "COALESCE(p.klas, '')" : "''"));
        $stmtTarget = $pdo->prepare("
            SELECT u.rol, {$classExpression} AS klas
            FROM users u
            {$profileJoin}
            {$classJoin}
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmtTarget->execute(['id' => $userId]);
        $target = $stmtTarget->fetch();

        if (
            !is_array($target)
            || (string) ($target['rol'] ?? '') !== 'leerling'
            || !leerkrachtMagKlasBeheren($pdo, (int) ($_SESSION['user_id'] ?? 0), (string) ($target['klas'] ?? ''))
        ) {
            setFlashMessage('error', 'U mag dit account niet aanpassen.');
            redirect('admin_gebruikers.php');
        }

        $rol = 'leerling';
    }

    if ($actie === 'verwijderen') {
        $heeftAanvragen = false;

        if (
            databaseTableExists($pdo, 'werkvergunning')
            && databaseColumnExists($pdo, 'werkvergunning', 'eigenaar_user_id')
        ) {
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM werkvergunning WHERE eigenaar_user_id = :id');
            $countStmt->execute(['id' => $userId]);
            $heeftAanvragen = (int) $countStmt->fetchColumn() > 0;
        }

        if (!$heeftAanvragen) {
            try {
                $pdo->beginTransaction();

                if (
                    databaseTableExists($pdo, 'user_klas_vak')
                    && databaseColumnExists($pdo, 'user_klas_vak', 'user_id')
                ) {
                    $deleteClasses = $pdo->prepare('DELETE FROM user_klas_vak WHERE user_id = :id');
                    $deleteClasses->execute(['id' => $userId]);
                }

                if (
                    databaseTableExists($pdo, 'user_profiel')
                    && databaseColumnExists($pdo, 'user_profiel', 'user_id')
                ) {
                    $deleteProfile = $pdo->prepare('DELETE FROM user_profiel WHERE user_id = :id');
                    $deleteProfile->execute(['id' => $userId]);
                }

                if (
                    databaseTableExists($pdo, 'wachtwoord_reset_token')
                    && databaseColumnExists($pdo, 'wachtwoord_reset_token', 'user_id')
                ) {
                    $deleteTokens = $pdo->prepare('DELETE FROM wachtwoord_reset_token WHERE user_id = :id');
                    $deleteTokens->execute(['id' => $userId]);
                }

                $deleteUser = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $deleteUser->execute(['id' => $userId]);

                $pdo->commit();
                setFlashMessage('success', 'Account volledig verwijderd.');
                redirect('admin_gebruikers.php');
            } catch (Throwable $deleteException) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Hard delete user failed, falling back to deactivate: ' . $deleteException->getMessage());
            }
        }

        $actief = 0;
    }

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

    setFlashMessage('success', $actie === 'verwijderen' ? 'Account werd verwijderd of veilig gedeactiveerd.' : 'Gebruiker succesvol aangepast.');
    redirect('admin_gebruikers.php');

} catch (Throwable $exception) {
    error_log('Admin user update failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Gebruiker aanpassen is mislukt.');
    redirect('admin_gebruikers.php');
}
