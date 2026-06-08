<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

$role = (string) ($_SESSION['rol'] ?? '');
$voornaam = trim((string) ($_SESSION['voornaam'] ?? ''));
$achternaam = trim((string) ($_SESSION['naam'] ?? ''));
$klas = trim((string) ($_SESSION['klas'] ?? ''));
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak II</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak2.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body
    data-user-role="<?= e($role) ?>"
    data-profile-name="<?= e(currentUserDisplayName()) ?>"
    data-profile-voornaam="<?= e($voornaam) ?>"
    data-profile-achternaam="<?= e($achternaam) ?>"
    data-profile-tel="<?= e((string) ($_SESSION['telefoon'] ?? '')) ?>"
    data-profile-email="<?= e((string) ($_SESSION['email'] ?? '')) ?>"
>
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
            <span class="form-title-number">Nr. <input type="text" id="werkvergunning_nummer" value="Automatisch bij indienen" readonly></span>
        </div>

        <div class="form-section">
            <h2 class="section-title">Vak II. Uitvoering, planning en medewerkers</h2>
            <p class="step-help">Leg hier vast wie de werken uitvoert, wanneer ze plaatsvinden en wie verantwoordelijk is op de werkplek.</p>

            <div class="form-group">
                <label>Wie voert de werken uit?</label>
                <div class="checkbox-group" data-radio-choice>
                    <div class="checkbox-item">
                        <input type="radio" id="vak2_doel_school" name="vak2_doel" value="school" required>
                        <label for="vak2_doel_school">Leerlingen van school</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="radio" id="vak2_doel_externe" name="vak2_doel" value="externe" required>
                        <label for="vak2_doel_externe">Externe firma</label>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" id="vak2_school_group" hidden>
                    <label for="vak2_school_uitvoerder">Uitvoerende organisatie</label>
                    <input type="text" id="vak2_school_uitvoerder" name="vak2_school_uitvoerder" value="GTI Beveren" readonly data-preserve-value="true">
                </div>
                <div class="form-group" id="vak2_klas_group" hidden>
                    <label for="vak2_klas">Klas</label>
                    <input type="text" id="vak2_klas" name="vak2_klas" value="<?= e($klas) ?>" placeholder="bijv. 6ADB" data-preserve-value="<?= $klas !== '' ? 'true' : 'false' ?>">
                    <p class="field-note">Voor leerlingen wordt deze klas gebruikt om de juiste leerkracht te verwittigen.</p>
                </div>
                <div class="form-group" id="vak2_firma_group" hidden>
                    <label for="vak2_firma">Naam externe firma</label>
                    <input type="text" id="vak2_firma" name="vak2_firma" placeholder="Naam van de firma">
                    <p class="field-note">Externe werken worden opgevolgd door TA of admin.</p>
                </div>
            </div>

            <h3 class="subsection-title">Verantwoordelijke uitvoerder</h3>
            <p class="step-help">Dit is de persoon die op de werkplek aanspreekbaar is tijdens de uitvoering.</p>
            <div class="form-row">
                <div class="form-group">
                    <label for="uitvoerder_voornaam">Voornaam</label>
                    <input type="text" id="uitvoerder_voornaam" name="uitvoerder_voornaam" required>
                </div>
                <div class="form-group">
                    <label for="uitvoerder_naam">Naam</label>
                    <input type="text" id="uitvoerder_naam" name="uitvoerder_naam" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="vak2_datumwerken">Datum werken</label>
                    <input type="date" id="vak2_datumwerken" name="vak2_datumwerken" required>
                </div>
                <div class="form-group">
                    <label for="werktijd_van">Werktijd van</label>
                    <input type="time" id="werktijd_van" name="werktijd_van" required>
                </div>
                <div class="form-group">
                    <label for="werktijd_tot">Werktijd tot</label>
                    <input type="time" id="werktijd_tot" name="werktijd_tot" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Veiligheidstest</label>
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
                    <label>VCA-certificaten</label>
                    <div class="checkbox-group" data-ja-nee-target="#vca_geldig_tot_group">
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
                    <label for="vermoedelijke_duur">Vermoedelijke duur</label>
                    <input type="text" id="vermoedelijke_duur" name="vermoedelijke_duur" placeholder="bijv. 2 dagen" required>
                </div>
                <div class="form-group" id="vca_geldig_tot_group">
                    <label for="geldig_tot">Geldig tot (VCA)</label>
                    <input type="date" id="geldig_tot" name="geldig_tot">
                </div>
                <div class="form-group">
                    <label for="werkzaamheden">Werkzaamheden</label>
                    <input type="text" id="werkzaamheden" name="werkzaamheden" placeholder="bijv. montage, afbouw" required>
                </div>
            </div>

            <div class="form-section medewerkers-section">
                <h3 class="subsection-title">Medewerkers op de werf</h3>
                <p class="field-note">Voeg alle medewerkers toe die mee aan de werken uitvoeren.</p>
                <div class="dynamic-table" id="medewerkers_table" data-storage-key="medewerkers">
                    <div class="dynamic-row" data-row>
                        <div class="form-group">
                            <label>Voornaam</label>
                            <input type="text" data-field="voornaam">
                        </div>
                        <div class="form-group">
                            <label>Naam</label>
                            <input type="text" data-field="naam">
                        </div>
                        <div class="form-group">
                            <label>Telefoon</label>
                            <input type="tel" data-field="telefoon" data-optional="true">
                        </div>
                        <button class="remove-row" type="button" aria-label="Medewerker verwijderen">-</button>
                    </div>
                </div>
                <button class="add-row-btn" type="button" data-add-row="medewerkers_table">+ Medewerker toevoegen</button>
            </div>
        </div>

        <div class="navigation-buttons">
            <button class="nav-button prev" type="button" onclick="navigateToNext('werkvergunning_vak1.php')">Vorige</button>
            <button class="nav-button button next" type="button" onclick="navigateToNext('werkvergunning_vak2_activiteiten.php')">Volgende</button>
        </div>
    </div>
</main>
<script src="../JS/ui-feedback.js"></script>
<script src="../JS/saveCurrentVak.js"></script>
<script src="../JS/ja-nee-toggle.js"></script>
</body>
</html>
