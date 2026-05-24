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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .detail-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-field.full {
            grid-column: 1 / -1;
        }

        .detail-field label {
            font-weight: 700;
            color: #374151;
        }

        .readonly-box {
            padding: 12px 14px;
            border-radius: 10px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            color: #111827;
            min-height: 44px;
            white-space: pre-wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .status-goedgekeurd {
            background: #dcfce7;
            color: #166534;
        }

        .status-afgekeurd {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-wachtend {
            background: #fef3c7;
            color: #92400e;
        }

        .status-concept {
            background: #e5e7eb;
            color: #374151;
        }

        @media (max-width: 800px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <div class="readonly-box"><?= e((string) ($aanvraag['eigenaar_email'] ?? '')) ?></div>
                </div>

                <div class="detail-field">
                    <label>Rol aanvrager</label>
                    <div class="readonly-box"><?= e((string) ($aanvraag['eigenaar_rol'] ?? '')) ?></div>
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
</main>

<script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>