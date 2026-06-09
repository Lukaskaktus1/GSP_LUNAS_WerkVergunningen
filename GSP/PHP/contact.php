<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

$role = (string) ($_SESSION['rol'] ?? '');

$overzichtPagina = match ($role) {
    'leerling' => '../pages/overzicht_leerling.php',
    'leerkracht' => '../pages/overzicht_leerkracht.php',
    'ta' => '../pages/overzicht_ta.php',
    'directeur' => '../pages/overzicht_directeur.php',
    'admin' => '../pages/overzicht_admin.php',
    default => '../pages/overzicht_leerling.php',
};
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="../IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="../IMAGES/favicon-180.png?v=20260609">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/php_contact.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body>
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>Contact</p>
            </div>
        </div>
        <div class="header-center">
            <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
        </div>
        <div class="header-right">
            <button class="logout-btn" onclick="window.location.href='<?= e($overzichtPagina) ?>'">
                <i class="fas fa-arrow-left"></i>
                <span>Terug</span>
            </button>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span>Uitloggen</span>
            </button>
        </div>
    </header>

    <main class="main-container">
        <div class="form-card">
            <div class="form-title">
                <span>Contactgegevens</span>
            </div>
            <p><strong>Dienst:</strong> ICT</p>
            <p><strong>Telefoon:</strong> 03 000 00 00</p>
            <p><strong>E-mail:</strong> gti.werkvergunningen@gmail.com</p>

            <div class="form-section" style="margin-top:25px;">
                <h2 class="section-title">Contactformulier</h2>
                <div class="form-group">
                    <label for="contact_onderwerp">Onderwerp</label>
                    <input type="text" id="contact_onderwerp" placeholder="Bijvoorbeeld: Ik zie mijn werkvergunning niet verschijnen...">
                </div>
                <div class="form-group">
                    <label for="contact_bericht">Bericht</label>
                    <textarea id="contact_bericht" rows="5" placeholder="Schrijf hier uw bericht..."></textarea>
                </div>
                <button class="nav-button next" type="button" onclick="fakeSend()">
                    Verstuur
                </button>
            </div>
        </div>
    </main>
<script src="../JS/ui-feedback.js"></script>
    <script>
        function fakeSend() {
            if (typeof window.showAppPopup === 'function') {
                window.showAppPopup({
                    type: 'success',
                    title: 'Bericht verzonden',
                    message: 'Het bericht is succesvol verzonden.',
                    solution: 'We nemen zo snel mogelijk contact met u op.'
                });
                return;
            }

            console.info('Het bericht is succesvol verzonden. We nemen zo snel mogelijk contact met u op.');
        }
    </script>
</body>
</html>
