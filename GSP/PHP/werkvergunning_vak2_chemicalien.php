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
    <title>Werkvergunning Vak 2 - Chemicalien – GTI Beveren</title>
    <meta name="description" content="Werkvergunning formulier Vak 2 - Chemicalien. Registreer chemicaliën en stoffen die gebruikt worden.">
    <meta name="keywords" content="werkvergunning chemicalien, vak 2, GTI Beveren, veiligheid">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak2_chemicalien.php">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak2_chemicalien.php">
    <meta property="og:title" content="Werkvergunning Vak 2 - Chemicalien – GTI Beveren">
    <meta property="og:description" content="Werkvergunning formulier Vak 2 - Chemicalien. Registreer chemicaliën en stoffen.">
    <meta property="og:image" content="https://adbvandenweyer2205.be/afbeeldingen/LogoADB_1.png">
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Chemicaliën</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak2_chemicalien.css">
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

            <!-- II.5 CHEMICALIEN -->
            <div class="form-section">
                <div class="section-header-row">
                    <h3 class="subsection-title subsection-title-no-margin">II.5. CHEMICALIEN NODIG BIJ DE UITVOERING VAN HET WERK</h3>
                    <div class="checkbox-item geen-checkbox">
                        <input type="checkbox" id="chemicalien_geen" name="chemicalien_geen">
                        <label for="chemicalien_geen">GEEN</label>
                    </div>
                </div>
                <div class="checkbox-grid-4">
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_corrosief" name="chem_corrosief" value="1">
                        <label for="chem_corrosief" class="label-with-icon">
                            <img src="../IMAGES/Corrosief.png" alt="Corrosief" class="ghs-icon">
                            corrosief
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_toxisch" name="chem_toxisch" value="2">
                        <label for="chem_toxisch" class="label-with-icon">
                            <img src="../IMAGES/Giftig.png" alt="Giftig" class="ghs-icon">
                            (zeer) giftig
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_oxyderend" name="chem_oxyderend" value="3">
                        <label for="chem_oxyderend" class="label-with-icon">
                            <img src="../IMAGES/Oxiderend.png" alt="Oxiderend" class="ghs-icon">
                            oxyderend
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_ontvlambaar" name="chem_ontvlambaar" value="4">
                        <label for="chem_ontvlambaar" class="label-with-icon">
                            <img src="../IMAGES/Ontvlambaar.png" alt="Ontvlambaar" class="ghs-icon">
                            (z)(l)ontvlambaar
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_gassen" name="chem_gassen" value="5">
                        <label for="chem_gassen" class="label-with-icon">
                            <img src="../IMAGES/OpDruk.png" alt="Gassen" class="ghs-icon">
                            gassen (cilinders)
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_irriterend" name="chem_irriterend" value="6">
                        <label for="chem_irriterend" class="label-with-icon">
                            <img src="../IMAGES/Schadelijk.png" alt="Irriterend" class="ghs-icon">
                            irriterend
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_schadelijk" name="chem_schadelijk" value="7">
                        <label for="chem_schadelijk" class="label-with-icon">
                            <img src="../IMAGES/Schadelijk.png" alt="Schadelijk" class="ghs-icon">
                            schadelijk
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_explosief" name="chem_explosief" value="8">
                        <label for="chem_explosief" class="label-with-icon">
                            <img src="../IMAGES/Explosief.png" alt="Explosief" class="ghs-icon">
                            explosief
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_milieu" name="chem_milieu" value="9">
                        <label for="chem_milieu" class="label-with-icon">
                            <img src="../IMAGES/Milieu-Schadelijk.png" alt="Milieu-schadelijk" class="ghs-icon">
                            milieu-schadelijk
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="chem_gezondheid" name="chem_gezondheid" value="10">
                        <label for="chem_gezondheid" class="label-with-icon">
                            <img src="../IMAGES/LangeTermijnGezondheidsschade.png" alt="Lange termijn gezondheidsschadelijk" class="ghs-icon">
                            lange termijn gezondheidsschadelijk
                        </label>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak2_activiteiten.php'">Vorige</button>
                <button class="nav-button button next" onclick="navigateToNext('werkvergunning_vak3.php')">Volgende</button>

            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
        // Functie om GEEN checkbox logica te beheren voor chemicaliën sectie
        function setupChemicalienGeenCheckbox() {
            const geenCheckbox = document.getElementById('chemicalien_geen');
            if (!geenCheckbox) return;

            // Vind alle checkboxes in de chemicaliën sectie (behalve de "geen" checkbox zelf)
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"][id^="chem_"]:not(#chemicalien_geen)');

            // Event listener voor "geen" checkbox
            geenCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Als "geen" wordt aangevinkt: zet alle andere uit en disable ze
                    allCheckboxes.forEach(cb => {
                        cb.checked = false;
                        cb.disabled = true;
                    });
                } else {
                    // Als "geen" wordt uitgezet: enable alle andere weer
                    allCheckboxes.forEach(cb => {
                        cb.disabled = false;
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
                        });
                    }
                });
            });
        }

        // Setup voor chemicaliën sectie
        document.addEventListener('DOMContentLoaded', function() {
            setupChemicalienGeenCheckbox();
        });
    </script>
</body>
</html>
