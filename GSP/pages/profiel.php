<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    setFlashMessage('error', 'Log eerst in om verder te gaan.');
    redirect('../index.php');
}

$usesProfileTable = databaseTableExists($pdo, 'user_profiel') && databaseColumnExists($pdo, 'user_profiel', 'user_id');
$lastNameColumn = 'naam';

if ($usesProfileTable) {
    $lastNameColumn = databaseColumnExists($pdo, 'user_profiel', 'achternaam') ? 'achternaam' : 'naam';
    $profileColumns = optionalTableColumns($pdo, 'user_profiel', ['voornaam', $lastNameColumn, 'telefoon']);
} else {
    $profileColumns = optionalTableColumns($pdo, 'users', ['voornaam', 'naam', 'telefoon']);
}

function applyProfileDisplayDefaults(array $user): array
{
    foreach (['voornaam', 'naam', 'telefoon', 'email'] as $field) {
        if (trim((string) ($user[$field] ?? '')) === '' && isset($_SESSION[$field])) {
            $user[$field] = (string) $_SESSION[$field];
        }
    }

    return $user;
}

function loadCurrentUserProfile(PDO $pdo, int $userId, bool $usesProfileTable, string $lastNameColumn, array $profileColumns): ?array
{
    if ($usesProfileTable && $profileColumns !== []) {
        $userFallbackSelect = '';
        $userFallbackColumns = optionalTableColumns($pdo, 'users', ['voornaam', 'naam', 'telefoon']);

        if ($userFallbackColumns !== []) {
            $parts = [];
            foreach ($userFallbackColumns as $column) {
                $parts[] = "u.{$column} AS users_{$column}";
            }
            $userFallbackSelect = ', ' . implode(', ', $parts);
        }

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.email,
                p.voornaam AS profiel_voornaam,
                p.{$lastNameColumn} AS profiel_naam,
                p.telefoon AS profiel_telefoon
                {$userFallbackSelect}
            FROM users u
            LEFT JOIN user_profiel p ON p.user_id = u.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return null;
        }

        return applyProfileDisplayDefaults([
            'id' => (int) $row['id'],
            'email' => (string) ($row['email'] ?? ''),
            'voornaam' => (string) (($row['profiel_voornaam'] ?? '') !== '' ? $row['profiel_voornaam'] : ($row['users_voornaam'] ?? '')),
            'naam' => (string) (($row['profiel_naam'] ?? '') !== '' ? $row['profiel_naam'] : ($row['users_naam'] ?? '')),
            'telefoon' => (string) (($row['profiel_telefoon'] ?? '') !== '' ? $row['profiel_telefoon'] : ($row['users_telefoon'] ?? '')),
        ]);
    }

    $selectColumns = array_merge(['id', 'email'], $profileColumns);
    $stmt = $pdo->prepare(sprintf(
        'SELECT %s FROM users WHERE id = :id LIMIT 1',
        implode(', ', $selectColumns)
    ));
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        return null;
    }

    if (!isset($row['naam']) && isset($row['achternaam'])) {
        $row['naam'] = $row['achternaam'];
    }

    return applyProfileDisplayDefaults($row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'update');

    if ($action === 'delete') {
        $confirm = trim((string) ($_POST['confirm_delete'] ?? ''));

        if ($confirm !== 'VERWIJDEREN') {
            setFlashMessage('error', 'Typ exact VERWIJDEREN om uw account te verwijderen.');
            redirect('profiel.php#account-verwijderen');
        }

        $stmt = $pdo->prepare('UPDATE users SET actief = 0 WHERE id = :id');
        $stmt->execute(['id' => $userId]);

        session_destroy();
        redirect('../index.php');
    }

    if ($profileColumns === []) {
        setFlashMessage('error', 'Profielvelden zijn nog niet toegevoegd aan de database.');
        redirect('profiel.php');
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $values = [
        'voornaam' => trim((string) ($_POST['voornaam'] ?? '')),
        $lastNameColumn => trim((string) ($_POST['achternaam'] ?? $_POST['naam'] ?? '')),
        'telefoon' => trim((string) ($_POST['telefoon'] ?? '')),
    ];

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        setFlashMessage('error', 'Voer een geldig e-mailadres in.');
        redirect('profiel.php');
    }

    foreach ($profileColumns as $column) {
        if (($values[$column] ?? '') === '') {
            setFlashMessage('error', 'Vul alle profielvelden in.');
            redirect('profiel.php');
        }
    }

    try {
        $pdo->beginTransaction();

        $currentEmailStmt = $pdo->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $currentEmailStmt->execute(['id' => $userId]);
        $currentUser = $currentEmailStmt->fetch();
        $currentEmail = is_array($currentUser) ? strtolower((string) ($currentUser['email'] ?? '')) : '';

        if ($email !== $currentEmail) {
            $duplicateStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $duplicateStmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);

            if ($duplicateStmt->fetch()) {
                $pdo->rollBack();
                setFlashMessage('error', 'Er bestaat al een account met dit e-mailadres.');
                redirect('profiel.php');
            }

            $emailStmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $emailStmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);
        }

        if ($usesProfileTable) {
            $profileExistsStmt = $pdo->prepare('SELECT user_id FROM user_profiel WHERE user_id = :user_id LIMIT 1');
            $profileExistsStmt->execute(['user_id' => $userId]);

            if ($profileExistsStmt->fetch()) {
                $setParts = [];
                $params = ['user_id' => $userId];

                foreach ($profileColumns as $column) {
                    $setParts[] = "{$column} = :{$column}";
                    $params[$column] = $values[$column];
                }

                $stmt = $pdo->prepare('UPDATE user_profiel SET ' . implode(', ', $setParts) . ' WHERE user_id = :user_id');
                $stmt->execute($params);
            } else {
                $columns = array_merge(['user_id'], $profileColumns);
                $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
                $params = ['user_id' => $userId];

                foreach ($profileColumns as $column) {
                    $params[$column] = $values[$column];
                }

                $stmt = $pdo->prepare(sprintf(
                    'INSERT INTO user_profiel (%s) VALUES (%s)',
                    implode(', ', $columns),
                    implode(', ', $placeholders)
                ));
                $stmt->execute($params);
            }
        } else {
            $setParts = ['email = :email'];
            $params = [
                'id' => $userId,
                'email' => $email,
            ];

            foreach ($profileColumns as $column) {
                $setParts[] = "{$column} = :{$column}";
                $params[$column] = $values[$column];
            }

            $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id');
            $stmt->execute($params);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Profile update failed: ' . $exception->getMessage());

        if ($exception instanceof PDOException && $exception->getCode() === '23000') {
            setFlashMessage('error', 'Er bestaat al een account met dit e-mailadres.');
            redirect('profiel.php');
        }

        setFlashMessage('error', 'Uw gegevens konden niet worden opgeslagen. Probeer het later opnieuw.');
        redirect('profiel.php');
    }

    $_SESSION['voornaam'] = $values['voornaam'];
    $_SESSION['naam'] = $values[$lastNameColumn];
    $_SESSION['telefoon'] = $values['telefoon'];
    $_SESSION['email'] = $email;

    setFlashMessage('success', 'Uw gegevens zijn succesvol opgeslagen.');
    redirect('profiel.php');
}

$user = loadCurrentUserProfile($pdo, $userId, $usesProfileTable, $lastNameColumn, $profileColumns);

if ($user === null) {
    setFlashMessage('error', 'Gebruiker niet gevonden.');
    redirect('../logout.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn gegevens - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/profiel.css">
    <link rel="stylesheet" href="../CSS/local-icons.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon"><i class="far fa-file-lines"></i></div>
        <div class="header-title">
            <h1>Mijn gegevens</h1>
            <p><?= e(currentUserDisplayName()) ?></p>
        </div>
    </div>
    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>
    <div class="header-right">
        <button class="logout-btn" type="button" onclick="history.back()">
            <i class="fas fa-arrow-left"></i>
            <span>Terug</span>
        </button>
    </div>
</header>

<main class="main-container">
    <div class="form-card">
        <h2 class="section-title">Gegevens aanpassen</h2>
        <?= flashDialogMarkup($flash) ?>

        <form method="POST" action="profiel.php">
            <input type="hidden" name="action" value="update">

            <div class="form-row">
                <div class="form-group">
                    <label for="voornaam">Voornaam</label>
                    <input type="text" id="voornaam" name="voornaam" value="<?= e((string) ($user['voornaam'] ?? '')) ?>" autocomplete="given-name" required>
                </div>
                <div class="form-group">
                    <label for="naam">Achternaam</label>
                    <input type="text" id="naam" name="naam" value="<?= e((string) ($user['naam'] ?? '')) ?>" autocomplete="family-name" required>
                </div>
                <div class="form-group">
                    <label for="telefoon">Telefoonnummer</label>
                    <input type="tel" id="telefoon" name="telefoon" value="<?= e((string) ($user['telefoon'] ?? '')) ?>" autocomplete="tel" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" value="<?= e((string) ($user['email'] ?? '')) ?>" autocomplete="email" required>
            </div>

            <div class="navigation-buttons">
                <button class="nav-button next" type="submit">Gegevens opslaan</button>
            </div>
        </form>
    </div>

    <div class="form-card" id="account-verwijderen">
        <h2 class="section-title">Account verwijderen</h2>
        <p class="field-note">Typ exact <strong>VERWIJDEREN</strong> om uw account uit te schakelen.</p>
        <form method="POST" action="profiel.php">
            <input type="hidden" name="action" value="delete">
            <div class="form-group">
                <label for="confirm_delete">Bevestiging</label>
                <input type="text" id="confirm_delete" name="confirm_delete" placeholder="VERWIJDEREN" required>
            </div>
            <button class="nav-button prev" type="submit">Account verwijderen</button>
        </form>
    </div>
</main>
<script src="../JS/ui-feedback.js"></script>
</body>
</html>
