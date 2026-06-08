<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

$role = (string) ($_SESSION['rol'] ?? '');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak IV</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak4.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body data-user-role="<?= e($role) ?>">
<header class="header">
    <div class="header-left">
        <div class="header-icon"><i class="far fa-file-lines"></i></div>
        <div class="header-title">
            <h1>Werkvergunning Portaal</h1>
            <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(currentUserDisplayName()) ?></span></p>
        </div>
    </div>
    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>
    <div class="header-right">
        <button class="logout-btn" type="button" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i><span>Uitloggen</span>
        </button>
    </div>
</header>

<main class="main-container">
    <div class="form-card">
        <div class="form-title">
            <span>WERKVERGUNNING</span>
            <span class="form-title-number">Nr. <input type="text" id="werkvergunning_nummer" readonly></span>
        </div>

        <div class="form-section">
            <h2 class="section-title">Vak IV. AFDELING</h2>
            <p class="step-help">Vul in wie vanuit de afdeling bereikbaar is en of er andere werkzaamheden zijn die invloed kunnen hebben op de veiligheid.</p>

            <h3 class="subsection-title">Afdelingsverantwoordelijke</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="vak4_voornaam">Voornaam</label>
                    <input type="text" id="vak4_voornaam" name="vak4_voornaam">
                </div>
                <div class="form-group">
                    <label for="vak4_naam">Naam</label>
                    <input type="text" id="vak4_naam" name="vak4_naam">
                </div>
                <div class="form-group">
                    <label for="vak4_afdeling">Afdeling</label>
                    <input type="text" id="vak4_afdeling" name="vak4_afdeling">
                </div>
            </div>

            <h3 class="subsection-title">ORGANISATORISCHE AANDACHTSPUNTEN VANWEGE AFDELING</h3>

            <div class="checkbox-grid-2">
                <div class="checkbox-item">
                    <input type="checkbox" id="afd_geen" name="afd_geen" value="1">
                    <label for="afd_geen">GEEN</label>
                </div>
            </div>

            <div class="form-group" id="vak4_aandachtspunten_group">
                <label for="vak4_aandachtspunten">Andere werkzaamheden in de nabijheid</label>
                <input type="text" id="vak4_aandachtspunten" name="vak4_aandachtspunten" placeholder="Beschrijf andere werkzaamheden">
            </div>
        </div>

        <div class="navigation-buttons">
            <button class="nav-button prev" type="button" onclick="navigateToNext('werkvergunning_vak3.php')">Vorige</button>
            <button class="nav-button button next" type="button" onclick="navigateToNext('werkvergunning_vak5.php')">Volgende</button>
        </div>
    </div>
</main>
<script src="../JS/ui-feedback.js"></script>
<script src="../JS/saveCurrentVak.js"></script>
<script src="../JS/ja-nee-toggle.js"></script>
</body>
</html>
