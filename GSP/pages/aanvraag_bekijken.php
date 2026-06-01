<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$aanvraagId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$aanvraagId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

$pdo = getDbConnection();

$stmt = $pdo->prepare("
    SELECT *
    FROM werkvergunning
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    'id' => $aanvraagId,
]);

$aanvraag = $stmt->fetch();

if (!$aanvraag) {
    setFlashMessage('error', 'Aanvraag niet gevonden.');
    redirect('mijn_aanvragen.php');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = (string) ($_SESSION['rol'] ?? '');

$magAllesZien = in_array($role, ['directeur', 'ta', 'admin'], true);
$isEigenaar = (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId;

if (!$magAllesZien && !$isEigenaar) {
    setFlashMessage('error', 'U heeft geen toegang tot deze aanvraag.');
    redirect('mijn_aanvragen.php');
}

function haalGekoppeldeWaarden(PDO $pdo, string $sql, int $aanvraagId): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'vergunning_id' => $aanvraagId,
    ]);

    return array_values(array_filter(
        array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
        static fn (string $waarde): bool => $waarde !== ''
    ));
}

function toonLijst(array $waarden): string
{
    return $waarden === [] ? 'Geen geselecteerd' : implode(', ', $waarden);
}

$activiteitenKoud = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT ak.naam
        FROM vergunning_activiteit_koud vak
        INNER JOIN activiteit_koud ak ON ak.id = vak.activiteit_koud_id
        WHERE vak.vergunning_id = :vergunning_id
        ORDER BY ak.naam ASC
    ",
    $aanvraagId
);

$activiteitenWarm = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT aw.naam
        FROM vergunning_activiteit_warm vaw
        INNER JOIN activiteit_warm aw ON aw.id = vaw.activiteit_warm_id
        WHERE vaw.vergunning_id = :vergunning_id
        ORDER BY aw.naam ASC
    ",
    $aanvraagId
);

$machines = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT m.naam
        FROM vergunning_machine vm
        INNER JOIN machine m ON m.id = vm.machine_id
        WHERE vm.vergunning_id = :vergunning_id
        ORDER BY m.naam ASC
    ",
    $aanvraagId
);

$gevaarlijkeStoffen = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT gs.naam
        FROM vergunning_gevaarlijke_stof vgs
        INNER JOIN gevaarlijke_stof gs ON gs.id = vgs.gevaarlijke_stof_id
        WHERE vgs.vergunning_id = :vergunning_id
        ORDER BY gs.naam ASC
    ",
    $aanvraagId
);

$chemischePictogrammen = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT cp.naam
        FROM vergunning_chemisch_pictogram vcp
        INNER JOIN chemisch_pictogram cp ON cp.id = vcp.chemisch_pictogram_id
        WHERE vcp.vergunning_id = :vergunning_id
        ORDER BY cp.naam ASC
    ",
    $aanvraagId
);

$andereVergunningen = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT av.naam
        FROM vergunning_andere_vergunning vav
        INNER JOIN andere_vergunning av ON av.id = vav.andere_vergunning_id
        WHERE vav.vergunning_id = :vergunning_id
        ORDER BY av.naam ASC
    ",
    $aanvraagId
);

$toelatingen = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT t.naam
        FROM vergunning_toelating vt
        INNER JOIN toelating t ON t.id = vt.toelating_id
        WHERE vt.vergunning_id = :vergunning_id
        ORDER BY t.naam ASC
    ",
    $aanvraagId
);

$preventiemaatregelen = haalGekoppeldeWaarden(
    $pdo,
    "
        SELECT po.label_tekst
        FROM vergunning_preventie_item vpi
        INNER JOIN preventie_optie po ON po.id = vpi.preventie_optie_id
        WHERE vpi.vergunning_id = :vergunning_id
          AND vpi.aangevinkt = 1
        ORDER BY po.label_tekst ASC
    ",
    $aanvraagId
);

$medewerkers = [];
if (databaseTableExists($pdo, 'vergunning_medewerker')) {
    $stmtMedewerkers = $pdo->prepare("
        SELECT CONCAT(TRIM(CONCAT(COALESCE(voornaam, ''), ' ', COALESCE(naam, ''))), 
                      CASE WHEN telefoon IS NULL OR telefoon = '' THEN '' ELSE CONCAT(' - ', telefoon) END)
        FROM vergunning_medewerker
        WHERE vergunning_id = :vergunning_id
        ORDER BY id ASC
    ");
    $stmtMedewerkers->execute(['vergunning_id' => $aanvraagId]);
    $medewerkers = array_values(array_filter(array_map('strval', $stmtMedewerkers->fetchAll(PDO::FETCH_COLUMN))));
}

$voertuigAttesten = [];
if (databaseTableExists($pdo, 'vergunning_voertuig_attest')) {
    $stmtVoertuigen = $pdo->prepare("
        SELECT CONCAT(
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

function statusLabelAanvraag(string $status): string
{
    return match ($status) {
        'concept' => 'Concept',
        'ingediend' => 'Ingediend',
        'in_beoordeling' => 'In beoordeling',
        'goedgekeurd' => 'Goedgekeurd',
        'afgekeurd' => 'Afgekeurd',
        'afgemeld' => 'Afgemeld',
        'gesloten' => 'Gesloten',
        default => 'Onbekend',
    };
}

function statusClassAanvraag(string $status): string
{
    return match ($status) {
        'goedgekeurd' => 'status-goedgekeurd',
        'afgekeurd' => 'status-afgekeurd',
        'ingediend', 'in_beoordeling' => 'status-wachtend',
        default => 'status-concept',
    };
}

function terugNaarVorigePagina(): string
{
    $role = (string) ($_SESSION['rol'] ?? '');

    if (in_array($role, ['directeur', 'ta', 'admin'], true)) {
        return 'keuringen.php';
    }

    return 'mijn_aanvragen.php';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aanvraag bekijken - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="../CSS/aanvraag_bekijken.css">
    <link rel="stylesheet" href="../CSS/local-icons.css">
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
                Status:
                <span class="status-badge <?= e(statusClassAanvraag((string) ($aanvraag['status'] ?? ''))) ?>">
                    <?= e(statusLabelAanvraag((string) ($aanvraag['status'] ?? ''))) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='<?= e(terugNaarVorigePagina()) ?>'">
            <i class="fas fa-arrow-left"></i>
            <span>Terug</span>
        </button>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Uitloggen</span>
        </button>
    </div>
</header>

<main class="main-container">
    <section class="applications-section">
        <h2 class="section-title">Werkvergunning</h2>

        <div class="applications-container">
            <div class="detail-grid">
                <div class="detail-field">
                    <label>Vergunningnummer</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['vergunning_nummer'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Aanvrager</label>
                    <div class="readonly-box">
                        <?= e(trim((string) ($aanvraag['aanvrager_voornaam'] ?? '') . ' ' . (string) ($aanvraag['aanvrager_naam'] ?? '')) ?: (string) ($aanvraag['eigenaar_email'] ?? '')) ?>
                    </div>
                </div>

                <div class="detail-field">
                    <label>Telefoon aanvrager</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['aanvrager_telefoon'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Rol aanvrager</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['eigenaar_rol'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>School / firma</label>
                    <div class="readonly-box">
                        <?php if (array_key_exists('aanvrager_is_school', $aanvraag) && (int) ($aanvraag['aanvrager_is_school'] ?? 1) === 0): ?>
                            Externe firma: <?= e((string) ($aanvraag['firma_naam'] ?? '')) ?>
                        <?php else: ?>
                            School
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-field">
                    <label>Datum werken</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['datum_werken'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Werktijd van</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['werktijd_van'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Werktijd tot</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['werktijd_tot'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Vermoedelijke duur</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['vermoedelijke_duur'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Afdeling</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['afdeling_tekst'] ?? '')) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Werkbeschrijving</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['werkbeschrijving'] ?? '')) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Werkzaamheden</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['werkzaamheden'] ?? '')) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Medewerkers</label>
                    <div class="readonly-box"><?= e(toonLijst($medewerkers)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Voertuigen met attest</label>
                    <div class="readonly-box"><?= e(toonLijst($voertuigAttesten)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Aandachtspunten vak 3</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['aandachtspunten_vak3'] ?? '')) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Andere werkzaamheden</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['andere_werkzaamheden'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>EX-zone</label>
                    <div class="readonly-box"><?= (int) ($aanvraag['ex_zone'] ?? 0) === 1 ? 'Ja' : 'Nee' ?></div>
                </div>

                <div class="detail-field">
                    <label>Veiligheidstest</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['veiligheidstest_status'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>VCA verplicht</label>
                    <div class="readonly-box"><?= (int) ($aanvraag['vca_verplicht'] ?? 0) === 1 ? 'Ja' : 'Nee' ?></div>
                </div>

                <div class="detail-field">
                    <label>VCA geldig tot</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['vca_geldig_tot'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Aangemaakt</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['created_at'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Laatst aangepast</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['updated_at'] ?? '')) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="applications-section">
        <h2 class="section-title">Geselecteerde activiteiten en maatregelen</h2>

        <div class="applications-container">
            <div class="detail-grid">
                <div class="detail-field full">
                    <label>Koude activiteiten</label>
                    <div class="readonly-box"><?= e(toonLijst($activiteitenKoud)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Warme activiteiten</label>
                    <div class="readonly-box"><?= e(toonLijst($activiteitenWarm)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Machines / vervoer</label>
                    <div class="readonly-box"><?= e(toonLijst($machines)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Gevaarlijke stoffen</label>
                    <div class="readonly-box"><?= e(toonLijst($gevaarlijkeStoffen)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Chemische pictogrammen</label>
                    <div class="readonly-box"><?= e(toonLijst($chemischePictogrammen)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Andere vergunningen</label>
                    <div class="readonly-box"><?= e(toonLijst($andereVergunningen)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Bijkomende toelatingen</label>
                    <div class="readonly-box"><?= e(toonLijst($toelatingen)) ?></div>
                </div>

                <div class="detail-field full">
                    <label>Preventiemaatregelen</label>
                    <div class="readonly-box"><?= e(toonLijst($preventiemaatregelen)) ?></div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($magAllesZien && in_array((string) ($aanvraag['status'] ?? ''), ['ingediend', 'in_beoordeling'], true)): ?>
        <section class="applications-section">
            <h2 class="section-title">Keuring</h2>
            <div class="applications-container">
                <p>Keuring voor aanvraag <?= e((string) ($aanvraag['vergunning_nummer'] ?? '')) ?>.</p>
                <div class="approval-panel">
                    <form method="POST" action="aanvraag_keuren.php">
                        <input type="hidden" name="id" value="<?= (int) $aanvraagId ?>">
                        <input type="hidden" name="actie" value="afkeuren">
                        <button class="approval-btn reject" type="submit">Afkeuren</button>
                    </form>
                    <form method="POST" action="aanvraag_keuren.php">
                        <input type="hidden" name="id" value="<?= (int) $aanvraagId ?>">
                        <input type="hidden" name="actie" value="goedkeuren">
                        <button class="approval-btn approve" type="submit">Goedkeuren</button>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="../JS/ui-feedback.js"></script>
</body>
</html>
