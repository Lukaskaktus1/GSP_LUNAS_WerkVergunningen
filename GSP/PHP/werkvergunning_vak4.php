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
    <title>Werkvergunning - Vak IV</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(getCurrentUserRoleLabel()) ?></span></p>
            </div>
        </div>
        <div class="header-center">
            <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
        </div>
        <div class="header-right">
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span>Uitloggen</span>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="form-card">
            <div class="form-title">
                <span>WERKVERGUNNING</span>
                <span class="form-title-number">Nr. <input type="text" id="werkvergunning_nummer"></span>
            </div>

            <!-- Vak IV -->
            <div class="form-section">
                <h2 class="section-title">Vak IV. AFDELING</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="vak4_naam">Naam afdelingsverantwoordelijke:</label>
                        <input type="text" id="vak4_naam" name="vak4_naam">
                    </div>
                    <div class="form-group">
                        <label for="vak4_afdeling">Afdeling:</label>
                        <input type="text" id="vak4_afdeling" name="vak4_afdeling">
                    </div>
                </div>

                <h3 class="subsection-title">ORGANISATORISCHE AANDACHTSPUNTEN VANWEGE AFDELING (andere werkzaamheden in de nabijheid,...?)</h3>
                
                <div class="checkbox-grid-2">
                    <div class="checkbox-item">
                        <input type="checkbox" id="afd_geen" name="afd_geen">
                        <label for="afd_geen">GEEN</label>
                    </div>
                    <div class="form-group">
                        <label for="vak4_aandachtspunten">Andere werkzaamheden:</label>
                        <input type="text" id="vak4_aandachtspunten" name="vak4_aandachtspunten" placeholder="bijv. montage, afbouw, etc.">
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak3.php'">Vorige</button>
                <button class="nav-button button next" onclick="navigateToNext('werkvergunning_vak5.php')">Volgende</button>

            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
    </script>
</body>
</html>
