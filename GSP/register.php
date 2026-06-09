<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$flash = getFlashMessage();
$afdelingen = gspAfdelingen();
$klassen = gspKlassen();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="IMAGES/favicon-180.png?v=20260609">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Aanmaken – Werkvergunningen Portaal GTI Beveren</title>
    <meta name="description" content="Account aanmaken voor het werkvergunningen portaal. Registreer je nu als leerling, leerkracht of TA.">
    <meta name="keywords" content="werkvergunningen account aanmaken, GTI Beveren registratie, digitale werkvergunningen registreren">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e(appBaseUrl() . '/register.php') ?>">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(appBaseUrl() . '/register.php') ?>">
    <meta property="og:title" content="Account Aanmaken – Werkvergunningen Portaal GTI Beveren">
    <meta property="og:description" content="Account aanmaken voor het werkvergunningen portaal. Registreer je nu als leerling, leerkracht of TA.">
    <meta property="og:image" content="<?= e(appOrigin() . '/afbeeldingen/LogoADB_1.png') ?>">
    
    <link rel="stylesheet" href="CSS/style.css?v=20260608">
    <link rel="stylesheet" href="CSS/register.css?v=20260608">
    <link rel="stylesheet" href="CSS/local-icons.css?v=20260608">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="icon-container">
                <i class="far fa-file-lines"></i>
            </div>

            <h1>Account aanmaken</h1>
            <p class="subtitle">Maak een account aan voor het werkvergunning portaal</p>
                <?= flashDialogMarkup($flash) ?>

            <form class="login-form" action="register_verwerk.php" method="POST">
                <div class="form-group">
                    <label for="voornaam">Voornaam</label>
                    <input type="text" id="voornaam" name="voornaam" placeholder="Voornaam" autocomplete="given-name" required>
                </div>

                <div class="form-group">
                    <label for="naam">Naam</label>
                    <input type="text" id="naam" name="naam" placeholder="Familienaam" autocomplete="family-name" required>
                </div>

                <div class="form-group">
                    <label for="telefoon">Telefoonnummer</label>
                    <input type="tel" id="telefoon" name="telefoon" placeholder="+32 ..." autocomplete="tel" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" placeholder="naam@voorbeeld.be" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="rol">Ik registreer als</label>
                    <select id="rol" name="rol" required>
                        <option value="leerling">Leerling</option>
                        <option value="leerkracht">Leerkracht</option>
                    </select>
                </div>

                <div class="form-group register-role-only" id="leerling_klas_group">
                    <label for="klas">Klas</label>
                    <input type="text" id="klas" name="klas" placeholder="bijv. 6ADB">
                </div>

                <div class="form-group register-role-only" id="leerkracht_klassen_group" hidden style="display:none;">
                    <label>Klassen en vakken</label>
                    <p class="register-note">Voeg elke klas toe waaraan u les geeft, met het bijhorende vak.</p>
                    <div class="register-dynamic-table" id="leerkracht_klassen">
                        <div class="register-dynamic-row" data-register-row>
                            <select name="leerkracht_klas[]">
                                <option value="" disabled selected hidden>Kies klas</option>
                                <?php foreach ($klassen as $waarde => $label): ?>
                                    <option value="<?= e($waarde) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="leerkracht_vak[]">
                                <option value="" disabled selected hidden>Kies vak</option>
                                <?php foreach ($afdelingen as $waarde => $label): ?>
                                    <option value="<?= e($waarde) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="register-row-remove" aria-label="Klas verwijderen">-</button>
                        </div>
                    </div>
                    <button type="button" class="register-add-row" id="leerkracht_klas_toevoegen">+ Klas toevoegen</button>
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Min. 8 tekens, cijfer en speciaal teken"
                        pattern="(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                        title="<?= e(passwordPolicyMessage()) ?>"
                        autocomplete="new-password"
                        required
                    >
                    <ul class="password-criteria" id="password_criteria" aria-live="polite">
                        <li data-criterion="length">Minstens 8 tekens</li>
                        <li data-criterion="digit">Minstens 1 cijfer</li>
                        <li data-criterion="special">Minstens 1 speciaal teken</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Wachtwoord herhalen</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Herhaal wachtwoord" autocomplete="new-password" required>
                </div>

                <button type="submit" class="login-button" id="register_submit">Account aanmaken</button>
            </form>

            <p style="text-align:center; margin-top:16px;">
                Al een account?
                <a href="index.php">Terug naar inloggen</a>
            </p>
        </div>

        <div class="logo-card">
            <img src="IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="beveren-logo">
        </div>
    </div>
<script src="JS/ui-feedback.js"></script>
    <script src="JS/register-validation.js?v=20260609"></script>
</body>
</html>
