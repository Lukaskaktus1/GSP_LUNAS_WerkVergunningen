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
    <title>Werkvergunning Vak 6 – GTI Beveren | Digitale Werkvergunning</title>
    <meta name="description" content="Werkvergunning formulier Vak 6 - GTI Beveren. Vul de zesde sectie van je werkvergunning in.">
    <meta name="keywords" content="werkvergunning vak 6, GTI Beveren formulier, digitale werkvergunning">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak6.php">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak6.php">
    <meta property="og:title" content="Werkvergunning Vak 6 – GTI Beveren">
    <meta property="og:description" content="Werkvergunning formulier Vak 6 - Vul de zesde sectie van je werkvergunning in.">
    <meta property="og:image" content="https://adbvandenweyer2205.be/afbeeldingen/LogoADB_1.png">
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak VI</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak6.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
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
                <span>Vak VI. BEKRACHTIGING</span>
                <span class="warning-box inline">DEZE VERGUNNING VERVALT OGENBLIKKELIJK BIJ EEN NOODSITUATIE!</span>
            </div>
            <div class="form-section">
                <!-- Afdeling en Uitvoerder velden -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="vak6_afdeling">Afdeling:</label>
                        <input type="text" id="vak6_afdeling" name="vak6_afdeling" placeholder="Naam afdeling">
                    </div>
                    <div class="form-group">
                        <label for="vak6_uitvoerder">Uitvoerder:</label>
                        <input type="text" id="vak6_uitvoerder" name="vak6_uitvoerder" placeholder="Naam uitvoerder">
                    </div>
                </div>

                <!-- Table -->
                <table class="form-table" id="vak6_tabel">
                    <thead>
                        <tr>
                            <th rowspan="2">Dag</th>
                            <th rowspan="2">Datum</th>
                            <th rowspan="2">Van...</th>
                            <th rowspan="2">Tot...</th>
                            <th colspan="2">AFDELING</th>
                            <th colspan="2">UITVOERDER</th>
                            <th rowspan="2">Aantal uitvoerders</th>
                            <th rowspan="2">Afd.overdracht handtekening</th>
                        </tr>
                        <tr>
                            <th>Naam</th>
                            <th>paraaf</th>
                            <th>Naam</th>
                            <th>paraaf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="text"></td>
                            <td><input type="date"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                        </tr>
                        <tr>
                            <td><input type="text"></td>
                            <td><input type="date"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                        </tr>
                        <tr>
                            <td><input type="text"></td>
                            <td><input type="date"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                        </tr>
                        <tr>
                            <td><input type="text"></td>
                            <td><input type="date"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                        </tr>
                        <tr>
                            <td><input type="text"></td>
                            <td><input type="date"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text" value="u"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                            <td><input type="text"></td>
                        </tr>
                    </tbody>
                </table>
            
            <!-- LOTOTO Check -->
                <div class="warning-inline-container">
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                    <span>De LOTOTO-checklijst werd gecontroleerd op VOLLEDIGHEID en is OK voor VRIJGAVE INSTALLATIE:</span>
                    <div class="checkbox-group checkbox-group-inline">
                        <div class="checkbox-item">
                            <input type="checkbox" id="lototo_ja" name="lototo">
                            <label for="lototo_ja">Ja</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="lototo_nvt" name="lototo">
                            <label for="lototo_nvt">NVT</label>
                        </div>
                    </div>
                    <div id="loto_status_indicator" class="loto-status-indicator" style="display: none;">
                        <span class="material-symbols-outlined">info</span>
                        <span id="loto_status_text"></span>
                    </div>
                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                </div>

                <div class="info-box">
                    <p><strong>Afdeling:</strong> De vergunning is grondig voorbereid, de nodige vergunningen zijn bijgevoegd, de uitvoerders werden geïnformeerd, de werkplek werd geïnspecteerd!</p>
                    <p><strong>Uitvoerder:</strong> Ik ben akkoord met al de vereisten die besproken zijn, en informeer al mijn medewerkers deze maatregelen te handhaven.</p>
                </div>
            </div>
            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_preventie.php'">Vorige</button>
                <button class="nav-button button next" onclick="window.location.href='werkvergunning_vak7.php'">Volgende</button>

            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
        // Synchroniseer LOTO status met LOTOTO check
        function syncLOTOStatus() {
            const lotoStatus = sessionStorage.getItem('loto_status');
            const lotoRequired = sessionStorage.getItem('loto_required');
            const lototoJa = document.getElementById('lototo_ja');
            const lototoNvt = document.getElementById('lototo_nvt');
            const statusIndicator = document.getElementById('loto_status_indicator');
            const statusText = document.getElementById('loto_status_text');
            
            if (lotoRequired === 'true' && statusIndicator && statusText) {
                statusIndicator.style.display = 'flex';
                
                if (lotoStatus === 'nvt') {
                    statusText.textContent = 'LOTO status: Niet van toepassing (NVT)';
                    statusIndicator.style.backgroundColor = '#fff3cd';
                    statusIndicator.style.borderLeft = '4px solid #ffc107';
                    if (lototoNvt) {
                        lototoNvt.checked = true;
                        lototoJa.checked = false;
                    }
                } else if (lotoStatus === 'ingevuld') {
                    statusText.textContent = 'LOTO status: Volledig ingevuld';
                    statusIndicator.style.backgroundColor = '#d4edda';
                    statusIndicator.style.borderLeft = '4px solid #28a745';
                    if (lototoJa) {
                        lototoJa.checked = true;
                        lototoNvt.checked = false;
                    }
                } else {
                    statusText.textContent = 'LOTO status: Nog niet ingevuld';
                    statusIndicator.style.backgroundColor = '#f8d7da';
                    statusIndicator.style.borderLeft = '4px solid #dc3545';
                }
            }
        }

        // Setup Ja/NVT checkboxes om elkaar uit te sluiten
        function setupLototoCheckboxes() {
            const lototoJa = document.getElementById('lototo_ja');
            const lototoNvt = document.getElementById('lototo_nvt');
            
            if (lototoJa && lototoNvt) {
                lototoJa.addEventListener('change', function() {
                    if (this.checked) {
                        lototoNvt.checked = false;
                    }
                });
                
                lototoNvt.addEventListener('change', function() {
                    if (this.checked) {
                        lototoJa.checked = false;
                    }
                });
            }
        }

        // Navigatie terug functie
        function goBackFromVak6() {
            const lotoRequired = sessionStorage.getItem('loto_required');
            if (lotoRequired === 'true') {
                window.location.href = 'werkvergunning_loto.php';
            } else {
                window.location.href = 'werkvergunning_preventie.php';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            syncLOTOStatus();
            setupLototoCheckboxes();
        });
    </script>
</body>
</html>
