<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$aanvraagId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = (string) ($_SESSION['rol'] ?? '');
$magAllesZien = in_array($role, ['leerkracht', 'directeur', 'ta', 'admin'], true);
$foutmelding = null;

$aanvraag = null;
$activiteitenKoud = [];
$activiteitenWarm = [];
$machines = [];
$gevaarlijkeStoffen = [];
$chemischePictogrammen = [];
$andereVergunningen = [];
$toelatingen = [];
$preventiemaatregelen = [];
$medewerkers = [];
$voertuigAttesten = [];
$vak6Logs = [];
$vak7Afsluiting = null;
$beoordelingen = [];
$magVak6 = false;
$magVak7 = false;
$magKeuren = false;
$flash = null;

function terugNaarVorigePagina(bool $magAllesZien): string
{
    return $magAllesZien ? 'keuringen.php' : 'mijn_aanvragen.php';
}

function toonLijst(array $waarden): string
{
    return $waarden === [] ? 'Geen geselecteerd' : implode(', ', $waarden);
}

function toonWaarde(?string $waarde, string $leeg = 'Niet ingevuld'): string
{
    $waarde = trim((string) $waarde);

    return $waarde === '' ? $leeg : $waarde;
}

function afdelingLabel(?string $waarde): string
{
    $waarde = trim((string) $waarde);
    $afdelingen = gspAfdelingen();

    return $afdelingen[$waarde] ?? toonWaarde($waarde);
}

function volledigeNaam(?string $voornaam, ?string $naam, string $leeg = 'Niet ingevuld'): string
{
    $volledig = trim(trim((string) $voornaam) . ' ' . trim((string) $naam));

    return $volledig === '' ? $leeg : $volledig;
}

function toonJaNee(?string $waarde): string
{
    if ($waarde === null || trim($waarde) === '') {
        return 'Niet ingevuld';
    }

    return (int) $waarde === 1 || in_array(strtolower(trim($waarde)), ['ja', 'yes', '1', 'true'], true)
        ? 'Ja'
        : 'Nee';
}

function rolLabel(?string $role): string
{
    return match ((string) $role) {
        'leerling' => 'Leerling/Externe',
        'leerkracht' => 'Leerkracht',
        'ta' => 'TA',
        'directeur' => 'Directeur',
        'admin' => 'Admin',
        default => toonWaarde((string) $role),
    };
}

function tijdBereik(?string $van, ?string $tot): string
{
    $van = trim((string) $van);
    $tot = trim((string) $tot);

    if ($van === '' && $tot === '') {
        return 'Niet ingevuld';
    }

    if ($van === '') {
        return 'Tot ' . $tot;
    }

    if ($tot === '') {
        return 'Vanaf ' . $van;
    }

    return $van . ' - ' . $tot;
}

function uitvoerderTypeTekst(array $aanvraag): string
{
    $doel = trim((string) ($aanvraag['vak2_doel'] ?? ''));

    if ($doel === 'school') {
        return 'Leerlingen van school';
    }

    if ($doel === 'externe') {
        return 'Externe firma';
    }

    if (array_key_exists('aanvrager_is_school', $aanvraag)) {
        return (int) ($aanvraag['aanvrager_is_school'] ?? 0) === 1 ? 'Leerlingen van school' : 'Externe firma';
    }

    return 'Niet ingevuld';
}

function uitvoerendeOrganisatie(array $aanvraag): string
{
    $doel = trim((string) ($aanvraag['vak2_doel'] ?? ''));
    $isSchool = $doel === 'school'
        || ((int) ($aanvraag['aanvrager_is_school'] ?? 0) === 1 && $doel !== 'externe');

    if ($isSchool) {
        return 'GTI Beveren';
    }

    return toonWaarde((string) ($aanvraag['firma_naam'] ?? ''));
}

function renderDetailGrid(array $rows): void
{
    echo '<div class="detail-grid">';

    foreach ($rows as $row) {
        $label = (string) ($row['label'] ?? '');
        $value = (string) ($row['value'] ?? '');
        $full = !empty($row['full']);

        echo '<div class="detail-field' . ($full ? ' full' : '') . '">';
        echo '<label>' . e($label) . '</label>';
        echo '<div class="readonly-box">' . e($value) . '</div>';
        echo '</div>';
    }

    echo '</div>';
}

if (!$aanvraagId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect($magAllesZien ? 'keuringen.php' : 'mijn_aanvragen.php');
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $aanvraagId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag)) {
        setFlashMessage('error', 'Aanvraag niet gevonden.');
        redirect(terugNaarVorigePagina($magAllesZien));
    }

    if (!magVergunningBekijken($aanvraag, $userId, $role)) {
        setFlashMessage('error', 'U heeft geen toegang tot deze aanvraag.');
        redirect(terugNaarVorigePagina($magAllesZien));
    }

    $activiteitenKoud = haalGekoppeldeWaardenSafe($pdo, "
        SELECT ak.naam
        FROM vergunning_activiteit_koud vak
        INNER JOIN activiteit_koud ak ON ak.id = vak.activiteit_koud_id
        WHERE vak.vergunning_id = :vergunning_id
        ORDER BY ak.naam ASC
    ", $aanvraagId, 'vergunning_activiteit_koud');

    $activiteitenWarm = haalGekoppeldeWaardenSafe($pdo, "
        SELECT aw.naam
        FROM vergunning_activiteit_warm vaw
        INNER JOIN activiteit_warm aw ON aw.id = vaw.activiteit_warm_id
        WHERE vaw.vergunning_id = :vergunning_id
        ORDER BY aw.naam ASC
    ", $aanvraagId, 'vergunning_activiteit_warm');

    $machines = haalGekoppeldeWaardenSafe($pdo, "
        SELECT m.naam
        FROM vergunning_machine vm
        INNER JOIN machine m ON m.id = vm.machine_id
        WHERE vm.vergunning_id = :vergunning_id
        ORDER BY m.naam ASC
    ", $aanvraagId, 'vergunning_machine');

    $gevaarlijkeStoffen = haalGekoppeldeWaardenSafe($pdo, "
        SELECT gs.naam
        FROM vergunning_gevaarlijke_stof vgs
        INNER JOIN gevaarlijke_stof gs ON gs.id = vgs.gevaarlijke_stof_id
        WHERE vgs.vergunning_id = :vergunning_id
        ORDER BY gs.naam ASC
    ", $aanvraagId, 'vergunning_gevaarlijke_stof');

    $chemischePictogrammen = haalGekoppeldeWaardenSafe($pdo, "
        SELECT cp.naam
        FROM vergunning_chemisch_pictogram vcp
        INNER JOIN chemisch_pictogram cp ON cp.id = vcp.chemisch_pictogram_id
        WHERE vcp.vergunning_id = :vergunning_id
        ORDER BY cp.naam ASC
    ", $aanvraagId, 'vergunning_chemisch_pictogram');

    $andereVergunningen = haalGekoppeldeWaardenSafe($pdo, "
        SELECT av.naam
        FROM vergunning_andere_vergunning vav
        INNER JOIN andere_vergunning av ON av.id = vav.andere_vergunning_id
        WHERE vav.vergunning_id = :vergunning_id
        ORDER BY av.naam ASC
    ", $aanvraagId, 'vergunning_andere_vergunning');

    $toelatingen = haalGekoppeldeWaardenSafe($pdo, "
        SELECT t.naam
        FROM vergunning_toelating vt
        INNER JOIN toelating t ON t.id = vt.toelating_id
        WHERE vt.vergunning_id = :vergunning_id
        ORDER BY t.naam ASC
    ", $aanvraagId, 'vergunning_toelating');

    $preventiemaatregelen = haalGekoppeldeWaardenSafe($pdo, "
        SELECT po.label_tekst
        FROM vergunning_preventie_item vpi
        INNER JOIN preventie_optie po ON po.id = vpi.preventie_optie_id
        WHERE vpi.vergunning_id = :vergunning_id
          AND vpi.aangevinkt = 1
        ORDER BY po.label_tekst ASC
    ", $aanvraagId, 'vergunning_preventie_item');

    if (databaseTableExists($pdo, 'vergunning_medewerker')) {
        if (databaseColumnExists($pdo, 'vergunning_medewerker', 'medewerker_id') && databaseTableExists($pdo, 'medewerker')) {
            $stmtMedewerkers = $pdo->prepare("
                SELECT TRIM(CONCAT(COALESCE(m.voornaam, ''), ' ', COALESCE(m.achternaam, '')))
                FROM vergunning_medewerker vm
                INNER JOIN medewerker m ON m.id = vm.medewerker_id
                WHERE vm.vergunning_id = :vergunning_id
                ORDER BY vm.id ASC
            ");
            $stmtMedewerkers->execute(['vergunning_id' => $aanvraagId]);
            $medewerkers = array_values(array_filter(array_map('strval', $stmtMedewerkers->fetchAll(PDO::FETCH_COLUMN))));
        } elseif (databaseColumnExists($pdo, 'vergunning_medewerker', 'voornaam') && databaseColumnExists($pdo, 'vergunning_medewerker', 'naam')) {
            $telefoonSql = databaseColumnExists($pdo, 'vergunning_medewerker', 'telefoon')
                ? "CASE WHEN telefoon IS NULL OR telefoon = '' THEN '' ELSE CONCAT(' - ', telefoon) END"
                : "''";
            $stmtMedewerkers = $pdo->prepare("
                SELECT CONCAT(TRIM(CONCAT(COALESCE(voornaam, ''), ' ', COALESCE(naam, ''))), {$telefoonSql})
                FROM vergunning_medewerker
                WHERE vergunning_id = :vergunning_id
                ORDER BY id ASC
            ");
            $stmtMedewerkers->execute(['vergunning_id' => $aanvraagId]);
            $medewerkers = array_values(array_filter(array_map('strval', $stmtMedewerkers->fetchAll(PDO::FETCH_COLUMN))));
        }
    }

    if (databaseTableExists($pdo, 'vergunning_voertuig_attest')) {
        $voertuigTypeSql = databaseColumnExists($pdo, 'vergunning_voertuig_attest', 'voertuig_type')
            ? "CASE WHEN voertuig_type IS NULL OR voertuig_type = '' THEN '' ELSE CONCAT('Voertuig ', voertuig_type, ': ') END"
            : "''";
        $stmtVoertuigen = $pdo->prepare("
            SELECT CONCAT(
                {$voertuigTypeSql},
                COALESCE(nummerplaat, ''),
                CASE WHEN attest_geldig_tot IS NULL THEN '' ELSE CONCAT(' - attest geldig tot ', attest_geldig_tot) END
            )
            FROM vergunning_voertuig_attest
            WHERE vergunning_id = :vergunning_id
            ORDER BY id ASC
        ");
        $stmtVoertuigen->execute(['vergunning_id' => $aanvraagId]);
        $voertuigAttesten = array_values(array_filter(array_map('trim', array_map('strval', $stmtVoertuigen->fetchAll(PDO::FETCH_COLUMN)))));
    }

    if (magVergunningVak6($aanvraag, $userId)) {
        ensureWerkvergunningVak6LogTable($pdo);
        initialiseerVak6Logs($pdo, $aanvraagId, $aanvraag);
        $vak6Logs = laadVak6Logs($pdo, $aanvraagId);
    } elseif (databaseTableExists($pdo, 'werkvergunning_vak6_log')) {
        $vak6Logs = laadVak6Logs($pdo, $aanvraagId);
    }

    if (databaseTableExists($pdo, 'werkvergunning_vak7_afsluiting')) {
        $stmtVak7 = $pdo->prepare('SELECT payload_json, is_volledig FROM werkvergunning_vak7_afsluiting WHERE vergunning_id = :id LIMIT 1');
        $stmtVak7->execute(['id' => $aanvraagId]);
        $vak7Row = $stmtVak7->fetch();

        if (is_array($vak7Row)) {
            $payload = json_decode((string) ($vak7Row['payload_json'] ?? ''), true);
            $vak7Afsluiting = [
                'is_volledig' => (int) ($vak7Row['is_volledig'] ?? 0) === 1,
                'payload' => is_array($payload) ? $payload : [],
            ];
        }
    }

    if (databaseTableExists($pdo, 'werkvergunning_beoordeling')) {
        $stmtBeoordeling = $pdo->prepare('
            SELECT beoordelaar_rol, actie, naam, opmerking, created_at
            FROM werkvergunning_beoordeling
            WHERE vergunning_id = :id
            ORDER BY created_at DESC
        ');
        $stmtBeoordeling->execute(['id' => $aanvraagId]);
        $beoordelingen = array_filter($stmtBeoordeling->fetchAll(), 'is_array');
    }

    $magVak6 = magVergunningVak6($aanvraag, $userId);
    $magVak7 = magVergunningVak7($aanvraag, $userId, $pdo);
    $magKeuren = magVergunningKeuren($pdo, $aanvraag, $userId, $role)
        && (int) ($aanvraag['eigenaar_user_id'] ?? 0) !== $userId;
    $flash = getFlashMessage();
} catch (Throwable $exception) {
    error_log('aanvraag_bekijken failed: ' . $exception->getMessage());
    $foutmelding = 'De aanvraag kon niet worden geladen. Controleer of alle databasetabellen aanwezig zijn.';
}

if (!is_array($aanvraag)) {
    $aanvraag = [];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aanvraag bekijken - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/aanvraag_bekijken.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="far fa-file-lines"></i>
        </div>
        <div class="header-title">
            <h1>Aanvraag bekijken</h1>
            <p>
                Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(currentUserDisplayName()) ?></span>
                <?php if ($aanvraag !== []): ?>
                    - Status:
                    <span class="status-badge <?= e(vergunningStatusClass((string) ($aanvraag['status'] ?? ''))) ?>">
                        <?= e(vergunningStatusLabel((string) ($aanvraag['status'] ?? ''))) ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" type="button" onclick="window.location.href='<?= e(terugNaarVorigePagina($magAllesZien)) ?>'">
            <i class="fas fa-arrow-left"></i>
            <span>Terug</span>
        </button>
    </div>
</header>

<main class="main-container">
    <?= flashDialogMarkup($flash) ?>

    <?php if ($foutmelding !== null): ?>
        <section class="applications-section">
            <div class="applications-container">
                <p><?= e($foutmelding) ?></p>
            </div>
        </section>
    <?php else: ?>
        <?php if ($magVak6 || $magVak7): ?>
            <section class="applications-section">
                <h2 class="section-title">Vervolgstappen</h2>
                <div class="applications-container">
                    <?php if ($magVak6): ?>
                        <p>Vul Vak VI hier in tijdens de uitvoering. Er staat eerst 1 rij klaar; voeg extra rijen toe wanneer de werken verder lopen.</p>
                        <?php
                        $verwachteVak6Dagen = count(berekenWerkdagenVoorVergunning($aanvraag));
                        $vak6Rows = array_values($vak6Logs);
                        $nietIngevuldeRows = array_values(array_filter($vak6Rows, static function (array $row): bool {
                            return !vak6LogIsVolledig($row);
                        }));

                        if ($vak6Rows !== [] && count($nietIngevuldeRows) === count($vak6Rows)) {
                            $vak6Rows = [reset($vak6Rows)];
                        }

                        if ($vak6Rows === []) {
                            $vak6Rows[] = ['log_datum' => (string) ($aanvraag['datum_werken'] ?? date('Y-m-d'))];
                        }
                        ?>
                        <form method="POST" action="aanvraag_vak6_opslaan.php" class="vak6-inline-form" id="vak6_inline_form">
                            <input type="hidden" name="vergunning_id" value="<?= (int) $aanvraagId ?>">
                            <input type="hidden" name="return_to" value="aanvraag_bekijken.php?id=<?= (int) $aanvraagId ?>">

                            <div class="vak6-toolbar">
                                <span>Verwacht aantal werkdagen: <?= (int) max(1, $verwachteVak6Dagen) ?></span>
                                <button class="small-btn" type="button" id="vak6_add_row">+ Rij toevoegen</button>
                            </div>

                            <div class="vak6-table-wrap">
                                <table class="aanvragen-table vak6-edit-table" id="vak6_edit_table">
                                    <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Van</th>
                                        <th>Tot</th>
                                        <th>Afdeling naam</th>
                                        <th>Afdeling paraaf</th>
                                        <th>Uitvoerder naam</th>
                                        <th>Uitvoerder paraaf</th>
                                        <th>Aantal uitvoerders</th>
                                        <th>Afd. overdracht handtekening</th>
                                        <th>Actie</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($vak6Rows as $index => $log): ?>
                                        <tr>
                                            <td><input type="date" name="logs[<?= (int) $index ?>][log_datum]" value="<?= e((string) ($log['log_datum'] ?? '')) ?>" required></td>
                                            <td><input type="time" name="logs[<?= (int) $index ?>][van_tijd]" value="<?= e((string) ($log['van_tijd'] ?? '')) ?>" required></td>
                                            <td><input type="time" name="logs[<?= (int) $index ?>][tot_tijd]" value="<?= e((string) ($log['tot_tijd'] ?? '')) ?>" required></td>
                                            <td><input type="text" name="logs[<?= (int) $index ?>][afdeling_naam]" value="<?= e((string) ($log['afdeling_naam'] ?? '')) ?>" required></td>
                                            <td><input type="text" name="logs[<?= (int) $index ?>][afdeling_paraaf]" value="<?= e((string) ($log['afdeling_paraaf'] ?? '')) ?>"></td>
                                            <td><input type="text" name="logs[<?= (int) $index ?>][uitvoerder_naam]" value="<?= e((string) ($log['uitvoerder_naam'] ?? '')) ?>" required></td>
                                            <td><input type="text" name="logs[<?= (int) $index ?>][uitvoerder_paraaf]" value="<?= e((string) ($log['uitvoerder_paraaf'] ?? '')) ?>"></td>
                                            <td><input type="number" min="0" name="logs[<?= (int) $index ?>][aantal_uitvoerders]" value="<?= e((string) ($log['aantal_uitvoerders'] ?? '')) ?>"></td>
                                            <td><input type="text" name="logs[<?= (int) $index ?>][overdracht_handtekening]" value="<?= e((string) ($log['overdracht_handtekening'] ?? '')) ?>"></td>
                                            <td><button class="row-remove-btn" type="button" data-remove-vak6-row>Verwijderen</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-actions vak6-actions">
                                <button class="small-btn open-btn" type="submit">Vak VI opslaan</button>
                                <?php if ($magVak7): ?>
                                    <button class="small-btn open-btn" type="button" onclick="window.location.href='aanvraag_vak7.php?id=<?= (int) $aanvraagId ?>'">
                                        Vak VII afsluiten
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php elseif ($magVak7): ?>
                        <button class="small-btn open-btn" type="button" onclick="window.location.href='aanvraag_vak7.php?id=<?= (int) $aanvraagId ?>'">
                            Vak VII afsluiten
                        </button>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="applications-section">
            <h2 class="section-title">Algemeen</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Vergunningnummer', 'value' => toonWaarde((string) ($aanvraag['vergunning_nummer'] ?? ''))],
                    ['label' => 'Status', 'value' => vergunningStatusLabel((string) ($aanvraag['status'] ?? ''))],
                    ['label' => 'Aangemaakt op', 'value' => toonWaarde((string) ($aanvraag['created_at'] ?? ''))],
                    ['label' => 'Laatst aangepast', 'value' => toonWaarde((string) ($aanvraag['updated_at'] ?? ''))],
                    ['label' => 'Rol aanvrager', 'value' => rolLabel((string) ($aanvraag['eigenaar_rol'] ?? ''))],
                    ['label' => 'Eigenaar e-mail', 'value' => toonWaarde((string) ($aanvraag['eigenaar_email'] ?? ''))],
                ]); ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak I - Aanvraag en werkplek</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Aanvrager', 'value' => volledigeNaam(
                        (string) ($aanvraag['aanvrager_voornaam'] ?? ''),
                        (string) ($aanvraag['aanvrager_naam'] ?? ''),
                        (string) ($aanvraag['eigenaar_email'] ?? 'Niet ingevuld')
                    )],
                    ['label' => 'Telefoon aanvrager', 'value' => toonWaarde((string) ($aanvraag['aanvrager_telefoon'] ?? ''))],
                    ['label' => 'Afdeling / vak', 'value' => afdelingLabel((string) ($aanvraag['afdeling_tekst'] ?? ''))],
                    ['label' => 'EX-zone', 'value' => toonJaNee(isset($aanvraag['ex_zone']) ? (string) $aanvraag['ex_zone'] : null)],
                    ['label' => 'Werkbeschrijving', 'value' => toonWaarde((string) ($aanvraag['werkbeschrijving'] ?? '')), 'full' => true],
                ]); ?>
                <?php if (!empty($aanvraag['vak1_foto_data'])): ?>
                    <div class="detail-field full">
                        <label>Foto werkplek of machine</label>
                        <img class="aanvraag-photo" src="<?= e((string) $aanvraag['vak1_foto_data']) ?>" alt="Foto werkplek of machine">
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak II - Uitvoering en planning</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Wie voert de werken uit?', 'value' => uitvoerderTypeTekst($aanvraag)],
                    ['label' => 'Uitvoerende organisatie', 'value' => uitvoerendeOrganisatie($aanvraag)],
                    ['label' => 'Klas', 'value' => toonWaarde((string) ($aanvraag['vak2_klas'] ?? ''), 'Niet van toepassing')],
                    ['label' => 'Verantwoordelijke uitvoerder', 'value' => volledigeNaam(
                        (string) ($aanvraag['uitvoerder_voornaam'] ?? ''),
                        (string) ($aanvraag['uitvoerder_naam'] ?? '')
                    )],
                    ['label' => 'Datum werken', 'value' => toonWaarde((string) ($aanvraag['datum_werken'] ?? ''))],
                    ['label' => 'Werktijd', 'value' => tijdBereik(
                        (string) ($aanvraag['werktijd_van'] ?? ''),
                        (string) ($aanvraag['werktijd_tot'] ?? '')
                    )],
                    ['label' => 'Vermoedelijke duur', 'value' => toonWaarde((string) ($aanvraag['vermoedelijke_duur'] ?? ''))],
                    ['label' => 'Veiligheidstest', 'value' => toonWaarde((string) ($aanvraag['veiligheidstest_status'] ?? ''))],
                    ['label' => 'VCA verplicht', 'value' => toonJaNee(isset($aanvraag['vca_verplicht']) ? (string) $aanvraag['vca_verplicht'] : null)],
                    ['label' => 'VCA geldig tot', 'value' => toonWaarde((string) ($aanvraag['vca_geldig_tot'] ?? ''), 'Niet van toepassing')],
                    ['label' => 'Werkzaamheden', 'value' => toonWaarde((string) ($aanvraag['werkzaamheden'] ?? '')), 'full' => true],
                    ['label' => 'Medewerkers op de werf', 'value' => toonLijst($medewerkers), 'full' => true],
                    ['label' => 'Voertuigen met attest', 'value' => toonLijst($voertuigAttesten), 'full' => true],
                ]); ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak II - Activiteiten, machines en stoffen</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'II.1 Activiteiten koud', 'value' => toonLijst($activiteitenKoud), 'full' => true],
                    ['label' => 'II.2 Activiteiten warm', 'value' => toonLijst($activiteitenWarm), 'full' => true],
                    ['label' => 'II.3 Vervoer en machines', 'value' => toonLijst($machines), 'full' => true],
                    ['label' => 'II.4 Schadelijke of gevaarlijke stoffen', 'value' => toonLijst($gevaarlijkeStoffen), 'full' => true],
                    ['label' => 'Chemische pictogrammen', 'value' => toonLijst($chemischePictogrammen), 'full' => true],
                ]); ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak III - Organisatorische aandachtspunten</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Aandachtspunten vanwege opdrachtgever', 'value' => toonWaarde((string) ($aanvraag['aandachtspunten_vak3'] ?? '')), 'full' => true],
                ]); ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak IV - Afdeling</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Afdelingsverantwoordelijke', 'value' => volledigeNaam(
                        (string) ($aanvraag['vak4_voornaam'] ?? ''),
                        (string) ($aanvraag['vak4_naam'] ?? ''),
                        toonWaarde((string) ($aanvraag['naam_afdelingsverantwoordelijke'] ?? ''))
                    )],
                    ['label' => 'Andere werkzaamheden in de nabijheid', 'value' => (int) ($aanvraag['vak4_geen_andere_werk'] ?? 0) === 1
                        ? 'GEEN'
                        : toonWaarde((string) ($aanvraag['andere_werkzaamheden'] ?? '')), 'full' => true],
                ]); ?>
            </div>
        </section>

        <section class="applications-section">
            <h2 class="section-title">Vak V - Vergunningen, toelatingen en preventie</h2>
            <div class="applications-container">
                <?php renderDetailGrid([
                    ['label' => 'Andere vergunningen', 'value' => toonLijst($andereVergunningen), 'full' => true],
                    ['label' => 'Bijkomende toelatingen', 'value' => toonLijst($toelatingen), 'full' => true],
                    ['label' => 'Preventiemaatregelen', 'value' => toonLijst($preventiemaatregelen), 'full' => true],
                ]); ?>
            </div>
        </section>

        <?php if (!$magVak6 && $vak6Logs !== []): ?>
            <section class="applications-section">
                <h2 class="section-title">Vak VI - Logboek uitvoering</h2>
                <div class="applications-container">
                    <div style="overflow-x:auto;">
                        <table class="aanvragen-table">
                            <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Van</th>
                                <th>Tot</th>
                                <th>Afdeling</th>
                                <th>Uitvoerder</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($vak6Logs as $log): ?>
                                <tr>
                                    <td><?= e((string) ($log['log_datum'] ?? '')) ?></td>
                                    <td><?= e((string) ($log['van_tijd'] ?? '')) ?></td>
                                    <td><?= e((string) ($log['tot_tijd'] ?? '')) ?></td>
                                    <td><?= e((string) ($log['afdeling_naam'] ?? '')) ?></td>
                                    <td><?= e((string) ($log['uitvoerder_naam'] ?? '')) ?></td>
                                    <td><?= (int) ($log['is_volledig'] ?? 0) === 1 ? 'Volledig' : 'Onvolledig' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (is_array($vak7Afsluiting) && $vak7Afsluiting['payload'] !== []): ?>
            <section class="applications-section">
                <h2 class="section-title">Vak VII - Afsluiting</h2>
                <div class="applications-container">
                    <div class="detail-grid">
                        <?php foreach ($vak7Afsluiting['payload'] as $sleutel => $waarde): ?>
                            <?php if (is_scalar($waarde) && trim((string) $waarde) !== ''): ?>
                                <div class="detail-field">
                                    <label><?= e(ucfirst(str_replace('_', ' ', (string) $sleutel))) ?></label>
                                    <div class="readonly-box"><?= e((string) $waarde) ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <div class="detail-field">
                            <label>Status afsluiting</label>
                            <div class="readonly-box"><?= $vak7Afsluiting['is_volledig'] ? 'Volledig' : 'Onvolledig' ?></div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($beoordelingen !== []): ?>
            <section class="applications-section">
                <h2 class="section-title">Beoordeling</h2>
                <div class="applications-container">
                    <div class="detail-grid">
                        <?php foreach ($beoordelingen as $beoordeling): ?>
                            <div class="detail-field">
                                <label>Actie</label>
                                <div class="readonly-box"><?= e((string) ($beoordeling['actie'] ?? '')) ?></div>
                            </div>
                            <div class="detail-field">
                                <label>Beoordeeld door</label>
                                <div class="readonly-box"><?= e((string) ($beoordeling['naam'] ?? '')) ?> (<?= e((string) ($beoordeling['beoordelaar_rol'] ?? '')) ?>)</div>
                            </div>
                            <div class="detail-field">
                                <label>Moment</label>
                                <div class="readonly-box"><?= e((string) ($beoordeling['created_at'] ?? '')) ?></div>
                            </div>
                            <div class="detail-field">
                                <label>Opmerking</label>
                                <div class="readonly-box"><?= e(toonWaarde((string) ($beoordeling['opmerking'] ?? ''))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($magKeuren && in_array((string) ($aanvraag['status'] ?? ''), ['ingediend', 'in_beoordeling'], true)): ?>
            <section class="applications-section">
                <h2 class="section-title">Keuring</h2>
                <div class="applications-container">
                    <p>Controleer de volledige aanvraag. De gegevens hierboven zijn alleen-lezen; de officiële goedkeuring gebeurt hier met uw handtekening.</p>
                    <div class="approval-panel">
                        <form method="POST" action="aanvraag_keuren.php">
                            <input type="hidden" name="id" value="<?= (int) $aanvraagId ?>">
                            <input type="hidden" name="actie" value="afkeuren">
                            <textarea name="opmerking" rows="3" placeholder="Reden of opmerking bij afkeuring"></textarea>
                            <button class="approval-btn reject" type="submit">Afkeuren</button>
                        </form>
                        <form method="POST" action="aanvraag_keuren.php" class="approval-signature-form">
                            <input type="hidden" name="id" value="<?= (int) $aanvraagId ?>">
                            <input type="hidden" name="actie" value="goedkeuren">
                            <label for="beoordelaar_handtekening_canvas">Handtekening beoordelaar</label>
                            <canvas id="beoordelaar_handtekening_canvas" class="approval-signature-canvas"></canvas>
                            <input type="hidden" id="beoordelaar_handtekening" name="beoordelaar_handtekening" required>
                            <textarea name="opmerking" rows="3" placeholder="Opmerking bij goedkeuring"></textarea>
                            <button class="approval-btn approve" type="submit">Goedkeuren</button>
                        </form>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<script src="../JS/ui-feedback.js"></script>
<script>
function initApprovalSignature() {
    const canvas = document.getElementById('beoordelaar_handtekening_canvas');
    const hidden = document.getElementById('beoordelaar_handtekening');
    if (!canvas || !hidden) return;

    const ctx = canvas.getContext('2d');
    let drawing = false;
    let lastX = 0;
    let lastY = 0;

    function resize() {
        canvas.width = canvas.offsetWidth;
        canvas.height = 150;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111827';
    }

    function point(event) {
        const source = event.touches && event.touches[0] ? event.touches[0] : event;
        const rect = canvas.getBoundingClientRect();
        return { x: source.clientX - rect.left, y: source.clientY - rect.top };
    }

    function start(event) {
        event.preventDefault();
        drawing = true;
        const current = point(event);
        lastX = current.x;
        lastY = current.y;
    }

    function move(event) {
        if (!drawing) return;
        event.preventDefault();
        const current = point(event);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(current.x, current.y);
        ctx.stroke();
        lastX = current.x;
        lastY = current.y;
        hidden.value = canvas.toDataURL();
    }

    function stop() {
        if (!drawing) return;
        drawing = false;
        hidden.value = canvas.toDataURL();
    }

    resize();
    window.addEventListener('resize', resize);
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', stop);

    const form = canvas.closest('form');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (hidden.value) return;
            event.preventDefault();
            if (typeof window.showAppPopup === 'function') {
                window.showAppPopup({
                    type: 'error',
                    title: 'Handtekening ontbreekt',
                    message: 'Teken eerst uw handtekening voor u de aanvraag officieel goedkeurt.',
                    solution: 'De vergunning kan pas goedgekeurd worden na ondertekening door leerkracht, TA of admin.'
                });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initApprovalSignature();
    const table = document.getElementById('vak6_edit_table');
    const addButton = document.getElementById('vak6_add_row');
    if (!table || !addButton) return;

    function nextIndex() {
        return table.querySelectorAll('tbody tr').length;
    }

    function nextDate() {
        const rows = table.querySelectorAll('tbody tr');
        const lastDateInput = rows.length > 0 ? rows[rows.length - 1].querySelector('input[type="date"]') : null;
        const raw = lastDateInput ? lastDateInput.value : '';
        if (!raw) return '';

        const date = new Date(raw + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return '';
        date.setDate(date.getDate() + 1);
        return date.toISOString().slice(0, 10);
    }

    function input(type, name, value, required) {
        const el = document.createElement('input');
        el.type = type;
        el.name = name;
        el.value = value || '';
        if (required) el.required = true;
        if (type === 'number') el.min = '0';
        return el;
    }

    addButton.addEventListener('click', function () {
        const index = nextIndex();
        const row = document.createElement('tr');
        const fields = [
            ['date', 'log_datum', nextDate(), true],
            ['time', 'van_tijd', '', true],
            ['time', 'tot_tijd', '', true],
            ['text', 'afdeling_naam', '', true],
            ['text', 'afdeling_paraaf', '', false],
            ['text', 'uitvoerder_naam', '', true],
            ['text', 'uitvoerder_paraaf', '', false],
            ['number', 'aantal_uitvoerders', '', false],
            ['text', 'overdracht_handtekening', '', false],
        ];

        fields.forEach(function (field) {
            const cell = document.createElement('td');
            cell.appendChild(input(field[0], 'logs[' + index + '][' + field[1] + ']', field[2], field[3]));
            row.appendChild(cell);
        });

        const actionCell = document.createElement('td');
        const removeButton = document.createElement('button');
        removeButton.className = 'row-remove-btn';
        removeButton.type = 'button';
        removeButton.dataset.removeVak6Row = 'true';
        removeButton.textContent = 'Verwijderen';
        actionCell.appendChild(removeButton);
        row.appendChild(actionCell);

        table.querySelector('tbody').appendChild(row);
    });

    table.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-vak6-row]');
        if (!button) return;

        const rows = table.querySelectorAll('tbody tr');
        const row = button.closest('tr');

        if (rows.length <= 1) {
            row.querySelectorAll('input').forEach(function (field) {
                field.value = '';
            });
            return;
        }

        row.remove();
        table.querySelectorAll('tbody tr').forEach(function (currentRow, rowIndex) {
            currentRow.querySelectorAll('input').forEach(function (field) {
                field.name = field.name.replace(/logs\[\d+\]/, 'logs[' + rowIndex + ']');
            });
        });
    });
});
</script>
</body>
</html>
