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
    <link rel="icon" type="image/png" sizes="32x32" href="../IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="../IMAGES/favicon-180.png?v=20260609">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning Preventie – GTI Beveren | Digitale Werkvergunning</title>
    <meta name="description" content="Preventie werkvergunning - GTI Beveren. Vul deze werkvergunning in voor preventiemaatregelen en risicobeperkingen.">
    <meta name="keywords" content="preventie werkvergunning, risicobepaling, GTI Beveren, veiligheid">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e(appBaseUrl() . '/PHP/werkvergunning_preventie.php') ?>">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(appBaseUrl() . '/PHP/werkvergunning_preventie.php') ?>">
    <meta property="og:title" content="Werkvergunning Preventie – GTI Beveren">
    <meta property="og:description" content="Preventie werkvergunning - Vul deze werkvergunning in voor preventiemaatregelen en risicobeperkingen.">
    <meta property="og:image" content="<?= e(appOrigin() . '/afbeeldingen/LogoADB_1.png') ?>">
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/werkvergunning_preventie.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body
    data-user-role="<?= e($role) ?>"
    data-profile-name="<?= e(currentUserDisplayName()) ?>"
    data-profile-tel="<?= e((string) ($_SESSION['telefoon'] ?? '')) ?>"
    data-profile-email="<?= e((string) ($_SESSION['email'] ?? '')) ?>"
>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(currentUserDisplayName()) ?></span></p>
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
            <div class="form-title form-title-column">
                <div>PREVENTIEMAATREGELEN OP BASIS VAN RISICO-ANALYSE</div>
                <div class="form-title-subtitle">Instruktie(s) op basis van risico-analyse</div>
            </div>

            <div class="form-section">
                <h3 class="subsection-title">PREVENTIEMAATREGELEN (Hourekening met de preventiehiërarchie: VERMIJDEN / VERVANGEN / AF SCHERMEN / PBM's / INSTRUCTIES!)</h3>
                <p class="step-help">Kies de maatregelen die echt nodig zijn op basis van de risicoanalyse. Alles wat u hier selecteert, komt in het overzicht voor de beoordelaar.</p>

                <div class="preventie-grid">
                    <!-- HUID -->
                    <div class="preventie-category">
                        <h3>HUID</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="huid_tyvec_classic" name="huid_tyvec_classic" value="1">
                                <label for="huid_tyvec_classic">Tyvec Classic (wit-pak)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="huid_tyvec_f" name="huid_tyvec_f" value="2">
                                <label for="huid_tyvec_f">Tyvec F (grijs-pak)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="huid_hitte" name="huid_hitte" value="3">
                                <label for="huid_hitte">hitte-werende kledij</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="huid_andere" name="huid_andere" value="4">
                                <label for="huid_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- OGEN/OREN -->
                    <div class="preventie-category">
                        <h3>OGEN/OREN</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="ogen_gelaatscherm" name="ogen_gelaatscherm" value="5">
                                <label for="ogen_gelaatscherm">gelaatscherm</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="ogen_goggles" name="ogen_goggles" value="6">
                                <label for="ogen_goggles">goggles (ruimzichtbril)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="oren_bescherming" name="oren_bescherming" value="7">
                                <label for="oren_bescherming">oorbescherming</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="ogen_verlichting" name="ogen_verlichting" value="8">
                                <label for="ogen_verlichting">bijkomende verlichting</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="ogen_andere" name="ogen_andere" value="9">
                                <label for="ogen_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- HAND/VOETEN -->
                    <div class="preventie-category">
                        <h3>HAND/VOETEN</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="hand_handschoenen" name="hand_handschoenen" value="10">
                                <label for="hand_handschoenen">handschoenen type...</label>
                                <input type="text" class="inline-input-very-small">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="hand_laarzen" name="hand_laarzen" value="11">
                                <label for="hand_laarzen">rubberen laarzen</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="hand_polsbeschermers" name="hand_polsbeschermers" value="12">
                                <label for="hand_polsbeschermers">polsbeschermers</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="hand_tape" name="hand_tape" value="13">
                                <label for="hand_tape">vast-TAPEN: handschoenen /laarzen</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="hand_andere" name="hand_andere" value="14">
                                <label for="hand_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- ADEMHALING -->
                    <div class="preventie-category">
                        <h3>ADEMHALING</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_afzuiging" name="adem_afzuiging" value="15">
                                <label for="adem_afzuiging">afzuiging/ventilatie voorzien</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_stofmasker" name="adem_stofmasker" value="16">
                                <label for="adem_stofmasker">stofmasker P1/P2/P3</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_halfgelaatsmasker" name="adem_halfgelaatsmasker" value="17">
                                <label for="adem_halfgelaatsmasker">halfgelaatsmasker type.....</label>
                                <input type="text" class="inline-input-small">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_volgelaatsmasker" name="adem_volgelaatsmasker" value="18">
                                <label for="adem_volgelaatsmasker">volgelaatsmasker type.....</label>
                                <input type="text" class="inline-input-small">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_luchtkap" name="adem_luchtkap" value="19">
                                <label for="adem_luchtkap">luchtkap</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="adem_autonoom" name="adem_autonoom" value="20">
                                <label for="adem_autonoom">autonome ademlucht</label>
                            </div>
                        </div>
                    </div>

                    <!-- VALLEN -->
                    <div class="preventie-category">
                        <h3>VALLEN</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_stelling" name="vallen_stelling" value="21">
                                <label for="vallen_stelling">stelling</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_collectief" name="vallen_collectief" value="22">
                                <label for="vallen_collectief">collectieve valbescherming</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_persoonlijk" name="vallen_persoonlijk" value="23">
                                <label for="vallen_persoonlijk">persoonlijke valbescherming</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_gekeurd" name="vallen_gekeurd" value="24">
                                <label for="vallen_gekeurd">gekeurd tot:</label>
                                <input type="date" class="inline-input-medium">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_dichtleggen" name="vallen_dichtleggen" value="25">
                                <label for="vallen_dichtleggen">dichtleggen openingen vloer</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="vallen_andere" name="vallen_andere" value="26">
                                <label for="vallen_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- COMMUNICATIE -->
                    <div class="preventie-category">
                        <h3>COMMUNICATIE</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_radio" name="comm_radio" value="27">
                                <label for="comm_radio">extra radio (portofoon)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_tweede_persoon" name="comm_tweede_persoon" value="28">
                                <label for="comm_tweede_persoon">+2° persoon</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_productinfo" name="comm_productinfo" value="29">
                                <label for="comm_productinfo">bezit productinfo (SDS-database)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_signalisatie" name="comm_signalisatie" value="30">
                                <label for="comm_signalisatie">signalisatie (pictogrammen,...)</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_markeren" name="comm_markeren" value="31">
                                <label for="comm_markeren">markeren werkplek</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_gevarentags" name="comm_gevarentags" value="32">
                                <label for="comm_gevarentags">aanbrengen gevarentags</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="comm_andere" name="comm_andere" value="33">
                                <label for="comm_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- ANDERE -->
                    <div class="preventie-category">
                        <h3>ANDERE</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="andere_handgraven" name="andere_handgraven" value="34">
                                <label for="andere_handgraven">handgraven tot ... cm</label>
                                <input type="text" class="inline-input-extra-small">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="andere_afdekken" name="andere_afdekken" value="35">
                                <label for="andere_afdekken">afdekken riolering</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="andere_stockageplaats" name="andere_stockageplaats" value="36">
                                <label for="andere_stockageplaats">aanduiden stockageplaats</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="andere_hijsplan" name="andere_hijsplan" value="37">
                                <label for="andere_hijsplan">goedgekeurd hijsplan kritische lasten</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="andere_andere" name="andere_andere" value="38">
                                <label for="andere_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>

                    <!-- MILIEU -->
                    <div class="preventie-category">
                        <h3>MILIEU</h3>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_lucht" name="milieu_lucht" value="39">
                                <label for="milieu_lucht">vermijden luchtverontreiniging</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_bodem" name="milieu_bodem" value="40">
                                <label for="milieu_bodem">vermijden bodemverontreiniging</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_water" name="milieu_water" value="41">
                                <label for="milieu_water">vermijden waterverontreiniging</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_afval" name="milieu_afval" value="42">
                                <label for="milieu_afval">afval in juiste container:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_isolatie" name="milieu_isolatie" value="43">
                                <label for="milieu_isolatie">isolatie (afgekoeld) in gesloten zakken</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_draaien" name="milieu_draaien" value="44">
                                <label for="milieu_draaien">vermijden onnodig draaien van machine</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="milieu_andere" name="milieu_andere" value="45">
                                <label for="milieu_andere">andere:</label>
                                <input type="text" class="inline-input-medium">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="signature-section">
                    <h3 class="subsection-title">PREVENTIEMAATREGELEN DIE HIERBOVEN NIET VOORKOMEN (ALLEEN PREVENTIEMAATREGELEN OVERNEMEN UIT RISICO-ANALYSE)</h3>
                    <div class="form-group">
                        <textarea
                            id="preventie_aanvullend"
                            name="preventie_aanvullend"
                            rows="4"
                            placeholder="Voeg hier aanvullende preventiemaatregelen toe..."
                            data-optional="true"
                        ></textarea>
                    </div>
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="handtekening_opdrachtgever">Handtekening Opdrachtgever:</label>
                            <div class="signature-container">
                                <canvas id="signatureCanvas_opdrachtgever" class="signature-canvas"></canvas>
                                <input type="hidden" id="handtekening_opdrachtgever" name="handtekening_opdrachtgever">
                                <button type="button" class="signature-clear-btn" onclick="clearSignature('signatureCanvas_opdrachtgever')">Wissen</button>
                            </div>
                            <div class="signature-date-container">
                                <label for="datum_opdrachtgever" class="signature-date-label">Datum:</label>
                                <input type="date" id="datum_opdrachtgever" name="datum_opdrachtgever" class="signature-date-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="handtekening_afdeling">Handtekening Afdeling:</label>
                            <div class="signature-container">
                                <canvas id="signatureCanvas_afdeling" class="signature-canvas"></canvas>
                                <input type="hidden" id="handtekening_afdeling" name="handtekening_afdeling">
                                <button type="button" class="signature-clear-btn" onclick="clearSignature('signatureCanvas_afdeling')">Wissen</button>
                            </div>
                            <div class="signature-date-container">
                                <label for="datum_afdeling" class="signature-date-label">Datum:</label>
                                <input type="date" id="datum_afdeling" name="datum_afdeling" class="signature-date-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" type="button" onclick="navigateToNext('werkvergunning_vak5.php')">Vorige</button>
                <form id="aanvraagOpslaanForm" action="../pages/aanvraag_opslaan.php" method="POST" style="margin: 0; flex: 1;">
                    <input type="hidden" name="aanvraag_data" id="aanvraag_data">
                    <button type="submit" class="nav-button next">Werkvergunning indienen</button>
                </form>
            </div>
        </div>
    </main>
<script src="../JS/ui-feedback.js"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script src="../JS/ja-nee-toggle.js"></script>
    <script>
        // Handtekening Canvas functionaliteit
        function initSignatureCanvas(canvasId, hiddenInputId) {
            const canvas = document.getElementById(canvasId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            // Canvas grootte instellen
            canvas.width = canvas.offsetWidth;
            canvas.height = 150;
            
            // Canvas styling
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // Event listeners voor tekenen
            function startDrawing(e) {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                lastX = e.clientX - rect.left;
                lastY = e.clientY - rect.top;
            }

            function draw(e) {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const currentX = e.clientX - rect.left;
                const currentY = e.clientY - rect.top;

                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(currentX, currentY);
                ctx.stroke();

                lastX = currentX;
                lastY = currentY;

                // Sla handtekening op als base64
                hiddenInput.value = canvas.toDataURL();
            }

            function stopDrawing() {
                if (isDrawing) {
                    isDrawing = false;
                    hiddenInput.value = canvas.toDataURL();
                }
            }

            // Mouse events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // Touch events voor mobiele apparaten
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });

            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });

            canvas.addEventListener('touchend', (e) => {
                e.preventDefault();
                const mouseEvent = new MouseEvent('mouseup', {});
                canvas.dispatchEvent(mouseEvent);
            });

            // Window resize handler
            window.addEventListener('resize', () => {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                canvas.width = canvas.offsetWidth;
                canvas.height = 150;
                ctx.putImageData(imageData, 0, 0);
            });
        }

        function clearSignature(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Clear hidden input
            const hiddenInputId = canvasId === 'signatureCanvas_opdrachtgever' ? 'handtekening_opdrachtgever' : 'handtekening_afdeling';
            const hiddenInput = document.getElementById(hiddenInputId);
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        }

        // Initialize signature canvases when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initSignatureCanvas('signatureCanvas_opdrachtgever', 'handtekening_opdrachtgever');
            initSignatureCanvas('signatureCanvas_afdeling', 'handtekening_afdeling');
        });
    </script>
    </body>
</html>
