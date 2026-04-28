<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'], $_SESSION['rol'])) {
    $redirects = [
        'leerling' => 'pages/overzicht_leerling.php',
        'leerkracht' => 'pages/overzicht_leerkracht.php',
        'ta' => 'pages/overzicht_ta.php',
        'directeur' => 'pages/overzicht_directeur.php',
    ];

    redirect($redirects[$_SESSION['rol']] ?? 'pages/overzicht_leerling.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inlogpagina - Werkvergunning Portaal</title>
        <link rel="stylesheet" href="CSS/style.css">
    </head>
    <body>
        <div class="login-container">
            <div class="login-card">
                <div class="icon-container">
                    <i class="far fa-file-lines"></i>
                </div>
                <h1>Werkvergunning Portaal</h1>
                <p class="subtitle">Log in om uw werkvergunning aan te vragen</p>

                <?php if ($flash !== null): ?>
                    <p style="margin-bottom:16px;color:#b42318;"><?= e($flash['message']) ?></p>
                <?php endif; ?>

                <form class="login-form" action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email">E-mailadres</label>
                        <input type="email" id="email" name="email" placeholder="naam@voorbeeld.be" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Wachtwoord</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                    </div>

                    <div class="form-options">
                        <a href="#" class="forgot-password">Wachtwoord vergeten?</a>
                    </div>

                    <button type="submit" class="login-button">Inloggen</button>
                </form>
            </div>

            <div class="logo-card">
                <img src="IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="beveren-logo">
            </div>
        </div>

        <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    </body>
</html>
