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
                <span class="form-title-number">Nr. <input type="text" id="werkvergunning_nummer" value="Automatisch bij indienen" readonly></span>
            </div>

            <!-- Vak II -->
            <div class="form-section">
                <h2 class="section-title">Vak II. Uitvoering, planning en medewerkers</h2>
                <p class="form-subtitle">Vul eerst de verantwoordelijke uitvoerder in. Extra medewerkers en voertuigen kunt u hieronder apart toevoegen.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label for="vak2_naam">Verantwoordelijke uitvoerder:</label>
                        <input type="text" id="vak2_naam" name="vak2_naam" required>
                    </div>
                    <div class="form-group">
                        <label for="vak2_firma">Firma / klas / dienst:</label>
                        <input type="text" id="vak2_firma" name="vak2_firma" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vak2_datumwerken">Datum werken:</label>
                        <input type="date" id="vak2_datumwerken" name="vak2_datumwerken" required>
                    </div>
                    <div class="form-group">
                        <label for="werktijd_van">Werktijd van:</label>
                        <input type="time" id="werktijd_van" name="werktijd_van" required>
                    </div>
                    <div class="form-group">
                        <label for="werktijd_tot">Werktijd tot:</label>
                        <input type="time" id="werktijd_tot" name="werktijd_tot" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Medewerkers</label>
                    <div class="dynamic-table" id="medewerkers_table" data-storage-key="medewerkers">
                        <div class="dynamic-row" data-row>
                            <div class="form-group">
                                <label>Voornaam</label>
                                <input type="text" data-field="voornaam" required>
                            </div>
                            <div class="form-group">
                                <label>Naam</label>
                                <input type="text" data-field="naam" required>
                            </div>
                            <div class="form-group">
                                <label>Telefoon</label>
                                <input type="tel" data-field="telefoon">
                            </div>
                            <button class="remove-row" type="button" aria-label="Medewerker verwijderen">-</button>
                        </div>
                    </div>
                    <button class="add-row-btn" type="button" data-add-row="medewerkers_table">+ Medewerker toevoegen</button>
                    <input type="hidden" id="vak2_medewerkers" name="vak2_medewerkers">
                </div>

                <div class="form-row">
                    <div class="form-group">

                        <label>Veiligheidstest:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="radio" id="vak2_veiligheidstest_ok" name="vak2_veiligheidstest" value="ok" required>
                                <label for="vak2_veiligheidstest_ok">OK</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" id="vak2_veiligheidstest_nok" name="vak2_veiligheidstest" value="nok" required>
                                <label for="vak2_veiligheidstest_nok">NOK</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>VCA-certificaten: Ja/Nee. Indien ja, kopiëren en bewaren (*)</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="radio" id="vca_ja" name="vca" value="ja" required>
                                <label for="vca_ja">Ja</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" id="vca_nee" name="vca" value="nee" required>
                                <label for="vca_nee">Nee</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="vermoedelijke_duur">Vermoedelijke duur:</label>
                        <input type="text" id="vermoedelijke_duur" name="vermoedelijke_duur" placeholder="bijv. 2 uur" required>
                    </div>
                    <div class="form-group">
                        <label for="geldig_tot">Geldig tot:</label>
                        <input type="date" id="geldig_tot" name="geldig_tot" required>
                    </div>
                    <div class="form-group">
                        <label for="werkzaamheden">Werkzaamheden:</label>
                        <input type="text" id="werkzaamheden" name="werkzaamheden" placeholder="bijv. montage, afbouw, etc." required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Voertuigen met attest</label>
                    <p class="field-note">Voeg per voertuig of machine de nummerplaat en attestdatum toe.</p>
                    <div class="dynamic-table" id="voertuigen_table" data-storage-key="voertuigen_attesten">
                        <div class="dynamic-row" data-row>
                            <div class="form-group">
                                <label>Nummerplaat</label>
                                <input type="text" data-field="nummerplaat" data-optional="true">
                            </div>
                            <div class="form-group">
                                <label>Attest geldig tot</label>
                                <input type="date" data-field="attest_geldig_tot" data-optional="true">
                            </div>
                            <button class="remove-row" type="button" aria-label="Voertuig verwijderen">-</button>
                        </div>
                    </div>
                    <button class="add-row-btn" type="button" data-add-row="voertuigen_table">+ Voertuig toevoegen</button>
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
