<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'], $_SESSION['rol'])) {
    $redirects = [
        'leerling' => 'pages/overzicht_leerling.php',
        'leerkracht' => 'pages/overzicht_leerkracht.php',
        'ta' => 'pages/overzicht_ta.php',
        'directeur' => 'pages/overzicht_directeur.php',
        'admin' => 'pages/overzicht_admin.php',
    ];

    redirect($redirects[$_SESSION['rol']] ?? 'pages/overzicht_leerling.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
    <head>
    <link rel="icon" type="image/png" sizes="32x32" href="IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="IMAGES/favicon-180.png?v=20260609">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Werkvergunningen Portaal Inloggen – GTI Beveren</title>
        <meta name="description" content="Login voor het werkvergunningen portaal. Leerlingen, leerkrachten en TA's kunnen hier inloggen voor werkvergunningen.">
        <meta name="keywords" content="GTI Beveren werkvergunningen login, digitale werkvergunningen inloggen">
        <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="<?= e(appBaseUrl() . '/index.php') ?>">
        
        <!-- Open Graph / Social Media -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?= e(appBaseUrl() . '/index.php') ?>">
        <meta property="og:title" content="Werkvergunningen Portaal Inloggen – GTI Beveren">
        <meta property="og:description" content="Login voor het werkvergunningen portaal. Leerlingen, leerkrachten en TA's kunnen hier inloggen.">
        <meta property="og:image" content="<?= e(appOrigin() . '/afbeeldingen/LogoADB_1.png') ?>">
        
    <link rel="stylesheet" href="CSS/style.css?v=20260609">
    <link rel="stylesheet" href="CSS/login.css?v=20260609">
        <link rel="stylesheet" href="CSS/local-icons.css?v=20260608">
</head>
    <body class="auth-page auth-login-page">
        <header class="auth-header">
            <div class="auth-header-left">
                <span class="auth-header-icon" aria-hidden="true"></span>
                <div>
                    <h1>Werkvergunning Portaal</h1>
                    <p>GTI Beveren</p>
                </div>
            </div>
            <img src="IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="auth-header-logo">
        </header>

        <div class="login-container">
            <div class="login-card">
                <div class="icon-container">
                    <i class="far fa-file-lines"></i>
                </div>
                <h1>Werkvergunning Portaal</h1>
                <p class="subtitle">Meld u aan om werkvergunningen te beheren en op te volgen.</p>
                <?= flashDialogMarkup($flash) ?>

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
                        <a href="wachtwoord_vergeten.php" class="forgot-password">Wachtwoord vergeten?</a>
                    </div>

                    <button type="submit" class="login-button">Inloggen</button>

                    <p class="auth-switch">
                        Nog geen account?
                        <a href="register.php">Account aanmaken</a>
                    </p>
                    
                </form>
            </div>
        </div>
<script src="JS/ui-feedback.js"></script>
</body>
</html>
