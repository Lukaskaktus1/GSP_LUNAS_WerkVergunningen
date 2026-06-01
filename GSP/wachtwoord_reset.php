<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($token === '') {
    setFlashMessage('error', 'Ongeldige herstel-link.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!passwordMeetsPolicy($password)) {
        setFlashMessage('error', passwordPolicyMessage());
        redirect('wachtwoord_reset.php?token=' . urlencode($token));
    }

    if ($password !== $passwordConfirm) {
        setFlashMessage('error', 'De wachtwoorden komen niet overeen.');
        redirect('wachtwoord_reset.php?token=' . urlencode($token));
    }

    try {
        $pdo = getDbConnection();
        $userId = findUserIdByPasswordResetToken($pdo, $token);

        if ($userId === null || $userId <= 0) {
            setFlashMessage('error', 'Deze herstel-link is ongeldig of vervallen.');
            redirect('index.php');
        }

        $activeCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id AND actief = 1 LIMIT 1');
        $activeCheck->execute(['id' => $userId]);

        if (!$activeCheck->fetch()) {
            setFlashMessage('error', 'Deze herstel-link is ongeldig of vervallen.');
            redirect('index.php');
        }

        completePasswordReset(
            $pdo,
            $userId,
            $token,
            password_hash($password, PASSWORD_DEFAULT)
        );

        setFlashMessage('success', 'Wachtwoord aangepast. U kunt nu inloggen.');
        redirect('index.php');
    } catch (Throwable $exception) {
        error_log('Password reset failed: ' . $exception->getMessage());
        setFlashMessage('error', 'Wachtwoord aanpassen is momenteel niet beschikbaar.');
        redirect('index.php');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw wachtwoord - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/wachtwoord_reset.css">
    <link rel="stylesheet" href="CSS/local-icons.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="icon-container">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Nieuw wachtwoord</h1>
            <p class="subtitle"><?= e(passwordPolicyMessage()) ?></p>
                <?= flashDialogMarkup($flash) ?>

            <form class="login-form" method="POST" action="wachtwoord_reset.php">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group">
                    <label for="password">Nieuw wachtwoord</label>
                    <input type="password" id="password" name="password" pattern="(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Nieuw wachtwoord herhalen</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>

                <button type="submit" class="login-button">Wachtwoord aanpassen</button>
            </form>
        </div>
    </div>
<script src="JS/ui-feedback.js"></script>
</body>
</html>
