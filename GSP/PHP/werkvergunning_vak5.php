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
    <title>Werkvergunning Vak 5 – GTI Beveren | Digitale Werkvergunning</title>
    <meta name="description" content="Werkvergunning formulier Vak 5 - GTI Beveren. Vul de vijfde sectie van je werkvergunning in.">
    <meta name="keywords" content="werkvergunning vak 5, GTI Beveren formulier, digitale werkvergunning">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak5.php">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak5.php">
    <meta property="og:title" content="Werkvergunning Vak 5 – GTI Beveren">
    <meta property="og:description" content="Werkvergunning formulier Vak 5 - Vul de vijfde sectie van je werkvergunning in.">
    <meta property="og:image" content="https://adbvandenweyer2205.be/afbeeldingen/LogoADB_1.png">
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak V</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak5.css">
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

            <!-- Vak V -->
            <div class="form-section">
                <h2 class="section-title">Vak V. OPDRACHTGEVER EN AFDELING</h2>
                
                <!-- ANDERE VERGUNNINGEN -->
                <div class="form-section">
                    <div class="section-header-row">
                        <h3 class="subsection-title subsection-title-no-margin">ANDERE VERGUNNINGEN?</h3>
                        <div class="checkbox-item geen-checkbox">
                            <input type="checkbox" id="andere_verg_geen" name="andere_verg_geen">
                            <label for="andere_verg_geen">GEEN</label>
                        </div>
                    </div>
                    <div class="checkbox-grid ">
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_betreding" name="verg_betreding" value="2">
                            <label for="verg_betreding">Betreding-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_electro" name="verg_electro" value="3">
                            <label for="verg_electro">Electro-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_graaf" name="verg_graaf" value="4">
                            <label for="verg_graaf">Graaf-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_hoogte" name="verg_hoogte" value="5">
                            <label for="verg_hoogte">Hoogte- (*)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_lijnbreking" name="verg_lijnbreking" value="6">
                            <label for="verg_lijnbreking">Lijnbreking-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_loto" name="verg_loto" value="7">
                            <label for="verg_loto">Loto-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_stelling" name="verg_stelling" value="8">
                            <label for="verg_stelling">Stelling- (**)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_tijdelijk" name="verg_tijdelijk" value="9">
                            <label for="verg_tijdelijk">Tijdelijke-</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="verg_vuur" name="verg_vuur" value="10">
                            <label for="verg_vuur">Vuur-</label>
                        </div>
                    </div>
                    <div class="info-box">
                        <p><strong>(*)</strong> "werkhoogte" = afstand tussen de vloeren de voeten of bij werken op een bordes/steiger > 1 meter boven de bordes/steigervloer (=boven de leuning)</p>
                        <p><strong>(**)</strong> Geen vergunning "werken op hoogte" noodzakelijk bij een goedgekeurde (door een bevoegd persoon) steiger/stelling.</p>
                    </div>
                </div>

                <!-- BIJKOMENDE TOELATINGEN -->
                <div class="form-section">
                    <div class="section-header-row">
                        <h3 class="subsection-title subsection-title-no-margin">BIJKOMENDE TOELATINGEN?</h3>
                        <div class="checkbox-item geen-checkbox">
                            <input type="checkbox" id="toel_geen" name="toel_geen">
                            <label for="toel_geen">GEEN</label>
                        </div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_muur_dak" name="toel_muur_dak" value="2">
                            <label for="toel_muur_dak">muur, dak & vloerdoorvoer</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_versperren" name="toel_versperren" value="3">
                            <label for="toel_versperren">versperren doorgangen</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_hijsen" name="toel_hijsen" value="4">
                            <label for="toel_hijsen">hijsen boven pipe-racks</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_bluswater" name="toel_bluswater" value="5">
                            <label for="toel_bluswater">gebruik bluswater</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_werken_bluswater" name="toel_werken_bluswater" value="6">
                            <label for="toel_werken_bluswater">werken aan bluswater/sprinklers</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="toel_alarm" name="toel_alarm" value="7">
                            <label for="toel_alarm">werken aan alarm-installatie</label>
                        </div>
                        <div class="checkbox-item checkbox-item-span-2">
                            <input type="checkbox" id="toel_andere" name="toel_andere" value="99">
                            <label for="toel_andere">andere:</label>
                            <input type="text" class="inline-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak4.php'">Vorige</button>
                <button class="nav-button button next" onclick="window.location.href='werkvergunning_preventie.php'">Volgende</button>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
        // Functie om GEEN checkbox logica te beheren per sectie in Vak V
        function setupVak5GeenCheckbox(geenId, sectionPrefix) {
            const geenCheckbox = document.getElementById(geenId);
            if (!geenCheckbox) return;

            // Vind alle checkboxes in deze sectie (behalve de "geen" checkbox zelf)
            const allCheckboxes = document.querySelectorAll(`input[type="checkbox"][id^="${sectionPrefix}_"]:not(#${geenId})`);

            // Event listener voor "geen" checkbox
            geenCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Als "geen" wordt aangevinkt: zet alle andere uit en disable ze
                    allCheckboxes.forEach(cb => {
                        cb.checked = false;
                        cb.disabled = true;
                        // Disable ook bijbehorende input velden (voor "andere:")
                        const inputField = cb.closest('.checkbox-item')?.querySelector('input[type="text"]');
                        if (inputField) {
                            inputField.disabled = true;
                        }
                    });
                } else {
                    // Als "geen" wordt uitgezet: enable alle andere weer
                    allCheckboxes.forEach(cb => {
                        cb.disabled = false;
                        const inputField = cb.closest('.checkbox-item')?.querySelector('input[type="text"]');
                        if (inputField) {
                            inputField.disabled = false;
                        }
                    });
                }
            });

            // Event listeners voor andere checkboxes in deze sectie
            allCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (this.checked) {
                        // Als een checkbox wordt aangevinkt: zet "geen" uit en enable "geen"
                        geenCheckbox.checked = false;
                        geenCheckbox.disabled = false;
                        // Enable alle andere checkboxes in deze sectie
                        allCheckboxes.forEach(otherCb => {
                            otherCb.disabled = false;
                            const inputField = otherCb.closest('.checkbox-item')?.querySelector('input[type="text"]');
                            if (inputField) {
                                inputField.disabled = false;
                            }
                        });
                    }
                });
            });
        }

        // Functie om naar volgende stap te navigeren
        function navigateToNextStep(url) {
            saveCurrentVak();
            const lotoCheckbox = document.getElementById('verg_loto');
            
            // Als LOTO checkbox is aangevinkt, ga naar LOTO pagina
            if (lotoCheckbox && lotoCheckbox.checked) {
                // Sla LOTO status op in sessionStorage
                sessionStorage.setItem('loto_required', 'true');
                window.location.href = 'werkvergunning_loto.php';
            } else {
                // Anders ga naar preventie pagina
                sessionStorage.setItem('loto_required', 'false');
                window.location.href = url || 'werkvergunning_preventie.php';
            }
        }

        // Setup voor elke sectie in Vak V
        document.addEventListener('DOMContentLoaded', function() {
            setupVak5GeenCheckbox('andere_verg_geen', 'verg');
            setupVak5GeenCheckbox('toel_geen', 'toel');
        });
    </script>
</body>
</html>
