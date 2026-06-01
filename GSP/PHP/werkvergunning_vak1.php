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

$aanvragerNaam = currentUserDisplayName();
$aanvragerTel = (string) ($_SESSION['telefoon'] ?? '');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak I</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-user-role="<?= e($role) ?>">
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>
                    Welkom,
                    <span class="role-badge">
                        <i class="fas fa-user"></i>
                        <?= e(getCurrentUserRoleLabel()) ?>
                    </span>
                </p>
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
                <span class="form-title-number">
                    Nr. <input type="text" id="werkvergunning_nummer" name="werkvergunning_nummer" value="Automatisch bij indienen" readonly>
                </span>
            </div>

            <!-- Vak I -->
            <div class="form-section">
                <h2 class="section-title">Vak I. OPDRACHTGEVER / TA:</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vak1_naam">Aanvrager:</label>
                        <input type="text" id="vak1_naam" name="vak1_naam" value="<?= e($aanvragerNaam) ?>" readonly data-preserve-value="true">
                    </div>

                    <div class="form-group">
                        <label for="vak1_tel">Tel:</label>
                        <input type="tel" id="vak1_tel" name="vak1_tel" value="<?= e($aanvragerTel) ?>" readonly data-preserve-value="true">
                    </div>

                    <div class="form-group">
                        <label for="vak1_afdeling">Afdeling:</label>
                        <input type="text" id="vak1_afdeling" name="vak1_afdeling" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Komt de aanvraag vanuit de school?</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="aanvrager_school_ja" name="aanvrager_is_school" value="ja" required>
                            <label for="aanvrager_school_ja">Ja, van de school</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="aanvrager_school_nee" name="aanvrager_is_school" value="nee" required>
                            <label for="aanvrager_school_nee">Nee, externe firma</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" id="firma_naam_group" hidden>
                    <label for="firma_naam">Firma</label>
                    <input type="text" id="firma_naam" name="firma_naam" placeholder="Naam van de firma">
                </div>

                <h3 class="subsection-title">I.2. EXPLOSIEVE ATMOSFEER (Ex-Zone):</h3>

                <div class="form-group">
                    <label>Werkzaamheden in explosieve atmosfeer (gas/stof):</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" id="vak1_exzone_ja" name="vak1_exzone" value="ja" required>
                            <label for="vak1_exzone_ja">Ja</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" id="vak1_exzone_neen" name="vak1_exzone" value="neen" required>
                            <label for="vak1_exzone_neen">Neen</label>
                        </div>
                    </div>
                </div>

                <h3 class="subsection-title">I.1. WERKBESCHRIJVING:</h3>

                <p class="form-subtitle">
                    Geef een duidelijke, gedetailleerde beschrijving van de werkzaamheden zodat de risico-analyse zo compleet mogelijk is.
                </p>

                <div class="form-group">
                    <textarea id="vak1_werkbeschrijving" name="vak1_werkbeschrijving" rows="8" placeholder="Beschrijf hier de werkzaamheden..." required></textarea>
                </div>
            <div class="navigation-buttons">
                <button class="nav-button prev" type="button" onclick="window.location.href='<?= e($overzichtPagina) ?>'">
                    Annuleren
                </button>

                <button class="nav-button next" type="button" onclick="navigateToNext('werkvergunning_vak2.php')">
                    Volgende
                </button>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/ui-feedback.js"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    </body>
</html>
