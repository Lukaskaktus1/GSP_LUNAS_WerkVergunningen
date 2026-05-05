<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account aanmaken - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="icon-container">
                <i class="far fa-file-lines"></i>
            </div>

            <h1>Account aanmaken</h1>
            <p class="subtitle">Maak een account aan voor het werkvergunning portaal</p>

            <?php if ($flash !== null): ?>
                <p style="margin-bottom:16px;color:#b42318;">
                    <?= e((string) ($flash['message'] ?? '')) ?>
                </p>
            <?php endif; ?>

            <form class="login-form" action="register_verwerk.php" method="POST">
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" placeholder="naam@voorbeeld.be" required>
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" placeholder="Minstens 8 tekens" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Wachtwoord herhalen</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Herhaal wachtwoord" required>
                </div>

                <button type="submit" class="login-button">Account aanmaken</button>
            </form>

            <p style="text-align:center; margin-top:16px;">
                Al een account?
                <a href="index.php">Terug naar inloggen</a>
            </p>
        </div>

        <div class="logo-card">
            <img src="IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="beveren-logo">
        </div>
    </div>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>