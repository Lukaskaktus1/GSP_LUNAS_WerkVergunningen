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
    <title>Werkvergunning Vak 7 – GTI Beveren | Digitale Werkvergunning</title>
    <meta name="description" content="Werkvergunning formulier Vak 7 - GTI Beveren. Vul de zevende sectie van je werkvergunning in.">
    <meta name="keywords" content="werkvergunning vak 7, GTI Beveren formulier, digitale werkvergunning">
    <meta name="author" content="Lukas Vandenweyer, Jonas De Meersman">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak7.php">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://adbvandenweyer2205.be/GSP/PHP/werkvergunning_vak7.php">
    <meta property="og:title" content="Werkvergunning Vak 7 – GTI Beveren">
    <meta property="og:description" content="Werkvergunning formulier Vak 7 - Vul de zevende sectie van je werkvergunning in.">
    <meta property="og:image" content="https://adbvandenweyer2205.be/afbeeldingen/LogoADB_1.png">
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkvergunning - Vak VII</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="../CSS/werkvergunning_vak7.css">
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
                <span>Vak VII. AFMELDING</span>
            </div>

            <!-- Control Table -->
            <div class="form-section">
                <table class="form-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>CONTROLE</th>
                            <th>J</th>
                            <th>N</th>
                            <th>NAAM</th>
                            <th>HANDTEKENING</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Uitvoerder vragen (eerste 3) -->
                        <tr>
                            <td class="role-label-cell" rowspan="3">
                                <span>Uitvoerder:</span>
                            </td>
                            <td>Werkplek proper achtergelaten?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="proper_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="proper_neen" class="table-checkbox"></td>
                            <td rowspan="3" class="signature-name-cell">
                                <input type="text" id="uitvoerder_voornaam" placeholder="Voornaam" class="table-input-full">
                                <input type="text" id="uitvoerder_achternaam" placeholder="Achternaam" class="table-input-full">
                            </td>
                            <td rowspan="3" class="signature-cell">
                                <div class="signature-table-cell">
                                    <canvas id="signatureCanvas_uitvoerder" class="signature-canvas-small"></canvas>
                                    <input type="hidden" id="handtekening_uitvoerder" name="handtekening_uitvoerder">
                                    <button type="button" class="signature-clear-btn-small" onclick="clearSignature('signatureCanvas_uitvoerder')">Wissen</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Is de taak volledig afgerond?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="taak_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="taak_neen" class="table-checkbox"></td>
                        </tr>
                        <tr>
                            <td>Is de installatie GETEST?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="getest_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="getest_neen" class="table-checkbox"></td>
                        </tr>

                        <!-- Opdrachtgever/Afdeling i.o.v. opdrachtgever vragen (vragen 4 en 5) -->
                        <tr>
                            <td class="role-selector-cell" rowspan="2">
                                <div class="role-checkbox-group">
                                    <div class="role-checkbox-item">
                                        <input type="checkbox" id="role_opdrachtgever" name="role_opdrachtgever_iov" value="opdrachtgever">
                                        <label for="role_opdrachtgever">Opdrachtgever :</label>
                                    </div>
                                    <div class="role-checkbox-item">
                                        <input type="checkbox" id="role_afdeling_iov" name="role_opdrachtgever_iov" value="afdeling_iov">
                                        <label for="role_afdeling_iov">Afdeling i.o.v. opdrachtgever:</label>
                                    </div>
                                </div>
                            </td>
                            <td>Is de installatie bedrijfsklaar?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="bedrijfsklaar_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="bedrijfsklaar_neen" class="table-checkbox"></td>
                            <td rowspan="2" class="signature-name-cell">
                                <input type="text" id="opdrachtgever_iov_voornaam" placeholder="Voornaam" class="table-input-full">
                                <input type="text" id="opdrachtgever_iov_achternaam" placeholder="Achternaam" class="table-input-full">
                            </td>
                            <td rowspan="2" class="signature-cell">
                                <div class="signature-table-cell">
                                    <canvas id="signatureCanvas_opdrachtgever_iov" class="signature-canvas-small"></canvas>
                                    <input type="hidden" id="handtekening_opdrachtgever_iov" name="handtekening_opdrachtgever_iov">
                                    <button type="button" class="signature-clear-btn-small" onclick="clearSignature('signatureCanvas_opdrachtgever_iov')">Wissen</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Opdrachtgever gecontacteerd?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="gecontacteerd_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="gecontacteerd_neen" class="table-checkbox"></td>
                        </tr>

                        <!-- Afdeling vraag (laatste) -->
                        <tr>
                            <td class="role-label-cell">
                                <span>Afdeling:</span>
                            </td>
                            <td>Is de maintenance taak volledig afgewerkt?</td>
                            <td class="checkbox-cell"><input type="checkbox" id="maintenance_ja" class="table-checkbox"></td>
                            <td class="checkbox-cell"><input type="checkbox" id="maintenance_neen" class="table-checkbox"></td>
                            <td class="signature-name-cell">
                                <input type="text" id="afdeling_voornaam" placeholder="Voornaam" class="table-input-full">
                                <input type="text" id="afdeling_achternaam" placeholder="Achternaam" class="table-input-full">
                            </td>
                            <td class="signature-cell">
                                <div class="signature-table-cell">
                                    <canvas id="signatureCanvas_afdeling" class="signature-canvas-small"></canvas>
                                    <input type="hidden" id="handtekening_afdeling" name="handtekening_afdeling">
                                    <button type="button" class="signature-clear-btn-small" onclick="clearSignature('signatureCanvas_afdeling')">Wissen</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- OPMERKINGEN -->
                <div class="remarks-section">
                    <h3 class="subsection-title">OPMERKINGEN?</h3>
                    <div class="remarks-header">
                        <span>Werkplekinspectie werd uitgevoerd?</span>
                        <div class="checkbox-group checkbox-group-inline-flex">
                            <div class="checkbox-item">
                                <input type="checkbox" id="inspectie_ja" name="inspectie">
                                <label for="inspectie_ja">Ja</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="inspectie_neen" name="inspectie">
                                <label for="inspectie_neen">Neen</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>(handtekening inspecteur)</label>
                        <div class="signature-container">
                            <canvas id="signatureCanvas_inspecteur" class="signature-canvas"></canvas>
                            <input type="hidden" id="handtekening_inspecteur" name="handtekening_inspecteur">
                            <button type="button" class="signature-clear-btn" onclick="clearSignature('signatureCanvas_inspecteur')">Wissen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="nav-button prev" onclick="window.location.href='werkvergunning_vak6.php'">Vorige</button>
                <form id="aanvraagOpslaanForm" action="../pages/aanvraag_opslaan.php" method="POST" style="margin: 0; flex: 1;">
                    <input type="hidden" name="aanvraag_data" id="aanvraag_data">
                    <button type="submit" class="nav-button next">Werkvergunning indienen</button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
    <script src="../JS/saveCurrentVak.js"></script>
    <script>
        // Submit handler voor aanvraag opslaan
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('aanvraagOpslaanForm');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    saveVak7Data();

                    const aanvraagData = collectAllAanvraagData();
                    const aanvraagDataInput = document.getElementById('aanvraag_data');

                    if (!aanvraagData.fields.vak1_werkbeschrijving) {
                        alert('Werkbeschrijving ontbreekt. Ga terug naar Vak 1 en vul de werkbeschrijving in.');
                        e.preventDefault();
                        return;
                    }

                    if (aanvraagDataInput) {
                        aanvraagDataInput.value = JSON.stringify(aanvraagData);
                    }
                });
            }
        });

        // Sla Vak 7 formulierdata op naar sessionStorage
        function saveVak7Data() {
            // Table checkboxes
            saveCheckboxToSession('proper_ja', 'vak7_proper_ja');
            saveCheckboxToSession('proper_neen', 'vak7_proper_neen');
            saveCheckboxToSession('taak_ja', 'vak7_taak_ja');
            saveCheckboxToSession('taak_neen', 'vak7_taak_neen');
            saveCheckboxToSession('getest_ja', 'vak7_getest_ja');
            saveCheckboxToSession('getest_neen', 'vak7_getest_neen');
            saveCheckboxToSession('bedrijfsklaar_ja', 'vak7_bedrijfsklaar_ja');
            saveCheckboxToSession('bedrijfsklaar_neen', 'vak7_bedrijfsklaar_neen');
            saveCheckboxToSession('gecontacteerd_ja', 'vak7_gecontacteerd_ja');
            saveCheckboxToSession('gecontacteerd_neen', 'vak7_gecontacteerd_neen');
            saveCheckboxToSession('maintenance_ja', 'vak7_maintenance_ja');
            saveCheckboxToSession('maintenance_neen', 'vak7_maintenance_neen');
            
            // Role checkboxes
            saveCheckboxToSession('role_opdrachtgever', 'vak7_role_opdrachtgever');
            saveCheckboxToSession('role_afdeling_iov', 'vak7_role_afdeling_iov');
            
            // Text inputs
            saveInputToSession('uitvoerder_voornaam', 'vak7_uitvoerder_voornaam');
            saveInputToSession('uitvoerder_achternaam', 'vak7_uitvoerder_achternaam');
            saveInputToSession('opdrachtgever_iov_voornaam', 'vak7_opdrachtgever_iov_voornaam');
            saveInputToSession('opdrachtgever_iov_achternaam', 'vak7_opdrachtgever_iov_achternaam');
            saveInputToSession('afdeling_voornaam', 'vak7_afdeling_voornaam');
            saveInputToSession('afdeling_achternaam', 'vak7_afdeling_achternaam');
            
            // Inspection checkbox
            saveCheckboxToSession('inspectie_ja', 'vak7_inspectie_ja');
            saveCheckboxToSession('inspectie_neen', 'vak7_inspectie_neen');
            
            // Handtekeningen (hidden inputs)
            saveInputToSession('handtekening_uitvoerder', 'vak7_handtekening_uitvoerder');
            saveInputToSession('handtekening_opdrachtgever_iov', 'vak7_handtekening_opdrachtgever_iov');
            saveInputToSession('handtekening_afdeling', 'vak7_handtekening_afdeling');
            saveInputToSession('handtekening_inspecteur', 'vak7_handtekening_inspecteur');
        }

        function saveCheckboxToSession(elementId, sessionKey) {
            const element = document.getElementById(elementId);
            if (element) {
                sessionStorage.setItem(sessionKey, element.checked ? 'ja' : 'neen');
            }
        }

        function saveInputToSession(elementId, sessionKey) {
            const element = document.getElementById(elementId);
            if (element) {
                sessionStorage.setItem(sessionKey, element.value || '');
            }
        }

        // Verzamel alle aanvraaggegevens uit sessionStorage
        function collectAllAanvraagData() {

            const fields = {};
            const lists = {};
            const tables = {};
            const signatures = {};

            const fieldKeys = [
                'vak1_naam',
                'vak1_tel',
                'vak1_afdeling',
                'vak1_werkbeschrijving',
                'vak1_exzone',

                'vak2_naam',
                'vak2_firma',
                'vak2_veiligheidstest',
                'vak2_datumwerken',
                'vak2_medewerkers',

                'vak3_aandachtspunten',
                'vak3_parkeerplaats',

                'vak4_naam',
                'vak4_afdeling',
                'vak4_aandachtspunten',

                'vak6_afdeling',
                'vak6_uitvoerder',

                'vak7_inspectie'
            ];

            const listKeys = [
                'vak2_act_koud',
                'vak2_act_warm',
                'vak2_vervoer',
                'vak2_stoffen',
                'vak2_chemicalien',
                'vak5_vergunningen',
                'vak5_toelatingen',
                'vak5_preventie'
            ];

            const tableKeys = [
                'vak6_tabel',
                'vak7_tabel'
            ];

            fieldKeys.forEach(key => {
                fields[key] = sessionStorage.getItem(key) || '';
            });

            listKeys.forEach(key => {
                lists[key] = sessionStorage.getItem(key) || '';
            });

            tableKeys.forEach(key => {
                tables[key] = sessionStorage.getItem(key) || '[]';
            });

            signatures.inspecteur = sessionStorage.getItem('handtekening_inspecteur') || '';

            return {
                fields: fields,
                lists: lists,
                tables: tables,
                signatures: signatures
            };
        }

        function initSignatureCanvas(canvasId, hiddenInputId, height = 150) {
            const canvas = document.getElementById(canvasId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            // Canvas grootte instellen
            canvas.width = canvas.offsetWidth;
            canvas.height = height;
            
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
                if (hiddenInput) {
                    hiddenInput.value = canvas.toDataURL();
                }
            }

            function stopDrawing() {
                if (isDrawing) {
                    isDrawing = false;
                    if (hiddenInput) {
                        hiddenInput.value = canvas.toDataURL();
                    }
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
                canvas.height = height;
                ctx.putImageData(imageData, 0, 0);
            });
        }

        function clearSignature(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Clear hidden input
            const hiddenInputMap = {
                'signatureCanvas_uitvoerder': 'handtekening_uitvoerder',
                'signatureCanvas_opdrachtgever_iov': 'handtekening_opdrachtgever_iov',
                'signatureCanvas_afdeling': 'handtekening_afdeling',
                'signatureCanvas_inspecteur': 'handtekening_inspecteur'
            };
            
            const hiddenInputId = hiddenInputMap[canvasId];
            if (hiddenInputId) {
                const hiddenInput = document.getElementById(hiddenInputId);
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }
        }

        // Maak radio button gedrag voor Ja/Nee checkboxes
        function setupJaNeeCheckboxes(jaId, neeId) {
            const jaCheckbox = document.getElementById(jaId);
            const neeCheckbox = document.getElementById(neeId);
            
            if (jaCheckbox && neeCheckbox) {
                jaCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        neeCheckbox.checked = false;
                    }
                });
                
                neeCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        jaCheckbox.checked = false;
                    }
                });
            }
        }

        // Initialize signature canvases when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Tabel handtekeningen (kleinere canvas) - één per rol groep
            initSignatureCanvas('signatureCanvas_uitvoerder', 'handtekening_uitvoerder', 200);
            initSignatureCanvas('signatureCanvas_opdrachtgever_iov', 'handtekening_opdrachtgever_iov', 150);
            initSignatureCanvas('signatureCanvas_afdeling', 'handtekening_afdeling', 100);
            // Inspecteur handtekening (grotere canvas)
            initSignatureCanvas('signatureCanvas_inspecteur', 'handtekening_inspecteur', 150);

            // Setup Ja/Nee checkboxes om elkaar uit te sluiten
            setupJaNeeCheckboxes('proper_ja', 'proper_neen');
            setupJaNeeCheckboxes('taak_ja', 'taak_neen');
            setupJaNeeCheckboxes('getest_ja', 'getest_neen');
            setupJaNeeCheckboxes('bedrijfsklaar_ja', 'bedrijfsklaar_neen');
            setupJaNeeCheckboxes('gecontacteerd_ja', 'gecontacteerd_neen');
            setupJaNeeCheckboxes('maintenance_ja', 'maintenance_neen');
            setupJaNeeCheckboxes('inspectie_ja', 'inspectie_neen');

            // Setup Opdrachtgever/Afdeling i.o.v. checkboxes om elkaar uit te sluiten
            const opdrachtgeverCheckbox = document.getElementById('role_opdrachtgever');
            const afdelingIovCheckbox = document.getElementById('role_afdeling_iov');
            
            if (opdrachtgeverCheckbox && afdelingIovCheckbox) {
                opdrachtgeverCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        afdelingIovCheckbox.checked = false;
                    }
                });
                
                afdelingIovCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        opdrachtgeverCheckbox.checked = false;
                    }
                });
            }
        });
    </script>
</body>
</html>
