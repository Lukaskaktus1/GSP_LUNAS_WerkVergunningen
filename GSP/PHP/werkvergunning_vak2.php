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
    <title>Werkvergunning - Vak II</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak2.css">
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

            <!-- Vak II -->
            <div class="form-section">
                <h2 class="section-title">Vak II. UITVOERDER/LEERLING:</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="vak2_naam">Naam:</label>
                        <input type="text" id="vak2_naam" name="vak2_naam">
                    </div>
                    <div class="form-group">
                        <label for="vak2_firma">Firma:</label>
                        <input type="text" id="vak2_firma" name="vak2_firma">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vak2_datumwerken">Datum werken:</label>
                        <input type="date" id="vak2_datumwerken" name="vak2_datumwerken">
                    </div>
                    <div class="form-group">
                        <label>werktijd van</label>
                        <input type="time" id="werktijd_van" name="werktijd_van">
                    </div>
                    <div class="form-group">
                        <label>tot</label>
                        <input type="time" id="werktijd_tot" name="werktijd_tot">
                    </div>
                </div>

                <div class="form-group">
                    <label for="vak2_medewerkers">Namen medewerkers:</label>
                    <input type="text" id="vak2_medewerkers" name="vak2_medewerkers">
                </div>

                <div class="form-row">
                    <div class="form-group">

                        <label>Veiligheidstest:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="radio" id="vak2_veiligheidstest_ok" name="vak2_veiligheidstest" value="ok">
                                <label for="vak2_veiligheidstest_ok">OK</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" id="vak2_veiligheidstest_nok" name="vak2_veiligheidstest" value="nok">
                                <label for="vak2_veiligheidstest_nok">NOK</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>VCA-certificaten: Ja/Nee.</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="radio" id="vca_ja" name="vca" value="ja">
                                <label for="vca_ja">Ja</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" id="vca_nee" name="vca" value="nee">
                                <label for="vca_nee">Nee</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="vermoedelijke_duur">Vermoedelijke duur:</label>
                        <input type="text" id="vermoedelijke_duur" name="vermoedelijke_duur" placeholder="bijv. 2 uur">
                    </div>
                    <div class="form-group">
                        <label for="geldig_tot">Geldig tot:</label>
                        <input type="date" id="geldig_tot" name="geldig_tot">
                    </div>
                    <!--- Lege kolom voor 3-kolommen layout --->
                    <div class="form-group"></div>
                    <!--- Einde Lege kolom voor 3-kolommen layout --->
                    <div class="form-group">
                        <label for="werkzaamheden">Werkzaamheden:</label>
                        <input type="text" id="werkzaamheden" name="werkzaamheden" placeholder="bijv. montage, afbouw, etc.">
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak1.php'">Vorige</button>
                <button class="nav-button button next" onclick="navigateToNext('werkvergunning_vak2_activiteiten.php')">Volgende</button>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
</body>
</html>
