<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        setFlashMessage('error', 'Voer een geldig e-mailadres in.');
        redirect('wachtwoord_vergeten.php');
    }

    try {
        $pdo = getDbConnection();

        if (!databaseColumnExists($pdo, 'users', 'reset_token') || !databaseColumnExists($pdo, 'users', 'reset_expires_at')) {
            setFlashMessage('error', 'Wachtwoord herstellen is nog niet geactiveerd in de database.');
            redirect('wachtwoord_vergeten.php');
        }

        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email AND actief = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (is_array($user)) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

            $update = $pdo->prepare('UPDATE users SET reset_token = :token, reset_expires_at = :expires_at WHERE id = :id');
            $update->execute([
                'token' => $token,
                'expires_at' => $expiresAt,
                'id' => (int) $user['id'],
            ]);

            $resetLink = appBaseUrl() . '/wachtwoord_reset.php?token=' . urlencode($token);
            $message = "Hallo,\n\nGebruik deze link om je wachtwoord opnieuw in te stellen:\n{$resetLink}\n\nDeze link blijft 1 uur geldig.";
            sendPortalMail((string) $user['email'], 'Wachtwoord herstellen Werkvergunning Portaal', $message);
        }

        setFlashMessage('success', 'Als dit e-mailadres bestaat, is er een herstelmail verstuurd.');
        redirect('index.php');
    } catch (Throwable $exception) {
        error_log('Password reset request failed: ' . $exception->getMessage());
        setFlashMessage('error', 'Wachtwoord herstellen is momenteel niet beschikbaar.');
        redirect('wachtwoord_vergeten.php');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord vergeten - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="icon-container">
                <i class="fas fa-key"></i>
            </div>
            <h1>Wachtwoord vergeten</h1>
            <p class="subtitle">Vul je e-mailadres in. Je krijgt een link om je wachtwoord opnieuw in te stellen.</p>

            <?php if ($flash !== null): ?>
                <p style="margin-bottom:16px;color:#b42318;"><?= e((string) ($flash['message'] ?? '')) ?></p>
            <?php endif; ?>

            <form class="login-form" method="POST" action="wachtwoord_vergeten.php">
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" placeholder="naam@voorbeeld.be" required>
                </div>

                <button type="submit" class="login-button">Herstelmail versturen</button>
            </form>

            <p style="text-align:center; margin-top:16px;">
                <a href="index.php">Terug naar inloggen</a>
            </p>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>
