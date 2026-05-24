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
    <title>Werkvergunning - Vak III</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak3.css">
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

            <!-- Vak III -->
            <div class="form-section">
                <h2 class="section-title">Vak III. OPDRACHTGEVER/TA:</h2>
                
                <h3 class="subsection-title">AANDACHTSPUNTEN OPDRACHTGEVER:</h3>
                
                <div class="form-group">
                    <label for="vak3_aandachtspunten">Organisatorische aandachtspunten vanwege opdrachtgever (andere werkzaamheden in de nabijheid, enz.):</label>
                    <textarea id="vak3_aandachtspunten" name="vak3_aandachtspunten" rows="6" placeholder="Beschrijf aandachtspunten..."></textarea>
                </div>

                <h3 class="subsection-title">PARKEERPLAATS:</h3>

                <div class="form-group">
                    <label for="vak3_parkeerplaats">Parkeerplaats informatie:</label>
                    <textarea id="vak3_parkeerplaats" name="vak3_parkeerplaats" rows="4" placeholder="Geef informatie over parkeerplaats..."></textarea>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak2_chemicalien.php'">Vorige</button>
                <button class="nav-button button next" onclick="navigateToNext('werkvergunning_vak4.php')">Volgende</button>

            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
        // Functie om GEEN checkbox logica te beheren voor Vak III
        document.addEventListener('DOMContentLoaded', function() {
            const geenCheckbox = document.getElementById('org_geen');
            const werkhoogteCheckbox = document.getElementById('org_werkhoogte');
            const andereCheckbox = document.getElementById('org_andere');
            const andereInput = andereCheckbox?.closest('.checkbox-item')?.querySelector('input[type="text"]');
            
            // Parkeerplaats veld blijft altijd enabled - vinden we via de section-divider
            const parkeerplaatsInput = document.querySelector('.section-divider input[type="text"]');
            
            if (!geenCheckbox) return;

            // Event listener voor "geen" checkbox
            geenCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Als "geen" wordt aangevinkt: zet alle andere uit en disable ze
                    if (werkhoogteCheckbox) {
                        werkhoogteCheckbox.checked = false;
                        werkhoogteCheckbox.disabled = true;
                    }
                    if (andereCheckbox) {
                        andereCheckbox.checked = false;
                        andereCheckbox.disabled = true;
                        if (andereInput) {
                            andereInput.disabled = true;
                        }
                    }
                    // Parkeerplaats blijft enabled (niets doen)
                } else {
                    // Als "geen" wordt uitgezet: enable alle andere weer
                    if (werkhoogteCheckbox) {
                        werkhoogteCheckbox.disabled = false;
                    }
                    if (andereCheckbox) {
                        andereCheckbox.disabled = false;
                        if (andereInput) {
                            andereInput.disabled = false;
                        }
                    }
                }
            });

            // Event listeners voor andere checkboxes
            if (werkhoogteCheckbox) {
                werkhoogteCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Als een checkbox wordt aangevinkt: zet "geen" uit en enable "geen"
                        geenCheckbox.checked = false;
                        geenCheckbox.disabled = false;
                        // Enable alle andere checkboxes
                        if (andereCheckbox) {
                            andereCheckbox.disabled = false;
                            if (andereInput) {
                                andereInput.disabled = false;
                            }
                        }
                    }
                });
            }

            if (andereCheckbox) {
                andereCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Als een checkbox wordt aangevinkt: zet "geen" uit en enable "geen"
                        geenCheckbox.checked = false;
                        geenCheckbox.disabled = false;
                        // Enable alle andere checkboxes
                        if (werkhoogteCheckbox) {
                            werkhoogteCheckbox.disabled = false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
