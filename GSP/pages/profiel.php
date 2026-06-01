<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);
$usesProfileTable = databaseTableExists($pdo, 'user_profiel') && databaseColumnExists($pdo, 'user_profiel', 'user_id');
$lastNameColumn = 'naam';

if ($usesProfileTable) {
    $lastNameColumn = databaseColumnExists($pdo, 'user_profiel', 'achternaam') ? 'achternaam' : 'naam';
    $profileColumns = optionalTableColumns($pdo, 'user_profiel', ['voornaam', $lastNameColumn, 'telefoon']);
} else {
    $profileColumns = optionalTableColumns($pdo, 'users', ['voornaam', 'naam', 'telefoon']);
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

    $values = [
        'voornaam' => trim((string) ($_POST['voornaam'] ?? '')),
        $lastNameColumn => trim((string) ($_POST['achternaam'] ?? $_POST['naam'] ?? '')),
        'telefoon' => trim((string) ($_POST['telefoon'] ?? '')),
    ];

    foreach ($profileColumns as $column) {
        if (($values[$column] ?? '') === '') {
            setFlashMessage('error', 'Vul alle profielvelden in.');
            redirect('profiel.php');
        }
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
        $setParts = [];
        $params = ['id' => $userId];

        foreach ($profileColumns as $column) {
            $setParts[] = "{$column} = :{$column}";
            $params[$column] = $values[$column];
        }

        $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    $_SESSION['voornaam'] = $values['voornaam'];
    $_SESSION['naam'] = $values[$lastNameColumn];
    $_SESSION['telefoon'] = $values['telefoon'];

    setFlashMessage('success', 'Uw gegevens zijn aangepast.');
    redirect('profiel.php');
}

if ($usesProfileTable && $profileColumns !== []) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, p.voornaam AS voornaam, p.{$lastNameColumn} AS naam, p.telefoon AS telefoon
        FROM users u
        LEFT JOIN user_profiel p ON p.user_id = u.id
        WHERE u.id = :id
        LIMIT 1
    ");
} else {
    $selectColumns = array_merge(['id', 'email'], $profileColumns);
    $stmt = $pdo->prepare(sprintf('SELECT %s FROM users WHERE id = :id LIMIT 1', implode(', ', $selectColumns)));
}
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!is_array($user)) {
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
        <button class="logout-btn" onclick="history.back()">
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
                    <input type="text" id="voornaam" name="voornaam" value="<?= e((string) ($user['voornaam'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="naam">Naam</label>
                    <input type="text" id="naam" name="naam" value="<?= e((string) ($user['naam'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefoon">Telefoonnummer</label>
                    <input type="tel" id="telefoon" name="telefoon" value="<?= e((string) ($user['telefoon'] ?? '')) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>E-mailadres</label>
                <input type="email" value="<?= e((string) ($user['email'] ?? '')) ?>" readonly>
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
