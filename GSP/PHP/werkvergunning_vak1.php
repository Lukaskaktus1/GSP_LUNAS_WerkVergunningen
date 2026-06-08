<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

$role = (string) ($_SESSION['rol'] ?? '');
$voornaam = trim((string) ($_SESSION['voornaam'] ?? ''));
$achternaam = trim((string) ($_SESSION['naam'] ?? ''));
$telefoon = (string) ($_SESSION['telefoon'] ?? '');
$email = (string) ($_SESSION['email'] ?? '');
$afdelingen = gspAfdelingen();

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
    <title>Werkvergunning - Vak I</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak1.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body
    data-user-role="<?= e($role) ?>"
    data-profile-voornaam="<?= e($voornaam) ?>"
    data-profile-achternaam="<?= e($achternaam) ?>"
    data-profile-tel="<?= e($telefoon) ?>"
    data-profile-email="<?= e($email) ?>"
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
            <span class="form-title-number">
                Nr. <input type="text" id="werkvergunning_nummer" name="werkvergunning_nummer" value="Automatisch bij indienen" readonly>
            </span>
        </div>

        <div class="form-section">
            <h2 class="section-title">Vak I. OPDRACHTGEVER / TA</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="aanvrager_voornaam">Voornaam aanvrager</label>
                    <input type="text" id="aanvrager_voornaam" name="aanvrager_voornaam" value="<?= e($voornaam) ?>" required data-preserve-value="true">
                </div>
                <div class="form-group">
                    <label for="aanvrager_naam">Naam aanvrager</label>
                    <input type="text" id="aanvrager_naam" name="aanvrager_naam" value="<?= e($achternaam) ?>" required data-preserve-value="true">
                </div>
                <div class="form-group">
                    <label for="vak1_tel">Telefoon</label>
                    <input type="tel" id="vak1_tel" name="vak1_tel" value="<?= e($telefoon) ?>" required data-preserve-value="true">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="aanvrager_email">E-mail</label>
                    <input type="email" id="aanvrager_email" name="aanvrager_email" value="<?= e($email) ?>" readonly data-preserve-value="true">
                </div>
                <div class="form-group">
                    <label for="vak1_afdeling">Afdeling</label>
                    <select id="vak1_afdeling" name="vak1_afdeling" required>
                        <option value="" disabled selected hidden>Kies afdeling</option>
                        <?php foreach ($afdelingen as $waarde => $label): ?>
                            <option value="<?= e($waarde) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field-note">Kies het vak of de afdeling waarvoor de werken gebeuren. Dit kan later nog aangepast worden indien nodig.</p>
                </div>
            </div>

            <h3 class="subsection-title">I.2. EXPLOSIEVE ATMOSFEER (Ex-Zone)</h3>
            <p class="step-help">Duid dit alleen aan wanneer de werken kunnen gebeuren in een zone met gas, stof of dampen die ontvlambaar kunnen zijn.</p>
            <div class="form-group">
                <label>Werkzaamheden in explosieve atmosfeer (gas/stof)</label>
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

            <h3 class="subsection-title">I.1. WERKBESCHRIJVING</h3>
            <p class="form-subtitle">Geef een duidelijke, gedetailleerde beschrijving van de werkzaamheden.</p>
            <p class="field-note">Vermeld lokaal, machinenummer, exacte plaats, taak, risico's en genoeg info zodat de controleur meteen begrijpt wat er zal gebeuren.</p>
            <div class="form-group">
                <textarea id="vak1_werkbeschrijving" name="vak1_werkbeschrijving" rows="8" required></textarea>
            </div>

            <div class="form-group">
                <label for="vak1_foto">Foto van werkplek of machine</label>
                <input type="file" id="vak1_foto" name="vak1_foto" accept="image/*" data-optional="true">
                <input type="hidden" id="vak1_foto_data" name="vak1_foto_data" data-optional="true">
                <p class="field-note">Voeg indien nuttig een foto toe van de machine, het lokaal of de plaats waar de werken gebeuren.</p>
                <img id="vak1_foto_preview" class="photo-preview" alt="Voorbeeld foto werkplek" hidden>
            </div>
        </div>

        <div class="navigation-buttons">
            <button class="nav-button prev" type="button" onclick="window.location.href='<?= e($overzichtPagina) ?>'">Annuleren</button>
            <button class="nav-button next" type="button" onclick="navigateToNext('werkvergunning_vak2.php')">Volgende</button>
        </div>
    </div>
</main>
<script src="../JS/ui-feedback.js"></script>
<script src="../JS/saveCurrentVak.js"></script>
<script src="../JS/ja-nee-toggle.js"></script>
</body>
</html>
