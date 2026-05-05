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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>Account</p>
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
                <span>Uw account</span>
            </div>

            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mailadres</label>
                        <input type="text" id="account_email" readonly value="<?= e($_SESSION['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <input type="text" id="account_role" readonly value="<?= e(getCurrentUserRoleLabel()) ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Instellingen (dummy)</h2>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="notif_email" checked>
                        <label for="notif_email">E-mailmeldingen voor nieuwe werkvergunningen</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="notif_reminders">
                        <label for="notif_reminders">Herinneringen voor lopende vergunningen</label>
                    </div>
                </div>
                <button class="nav-button next" type="button" onclick="saveDummySettings()">
                    Instellingen opslaan (demo)
                </button>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script>
        function saveDummySettings() {
            alert('Demo: instellingen worden niet echt opgeslagen.');
        }
    </script>
</body></html>
