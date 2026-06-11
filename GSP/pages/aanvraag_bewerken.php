<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$aanvraagId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$aanvraagId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

function bewerkKolom(PDO $pdo, array $row, string $column): string
{
    if (!databaseColumnExists($pdo, 'werkvergunning', $column)) {
        return '';
    }

    return (string) ($row[$column] ?? '');
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $aanvraagId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag)) {
        setFlashMessage('error', 'Aanvraag niet gevonden.');
        redirect('mijn_aanvragen.php');
    }

    if ((int) ($aanvraag['eigenaar_user_id'] ?? 0) !== $userId) {
        setFlashMessage('error', 'U mag deze aanvraag niet aanpassen.');
        redirect('mijn_aanvragen.php');
    }

    if ((string) ($aanvraag['status'] ?? '') !== 'ingediend') {
        setFlashMessage('error', 'Alleen ingediende aanvragen kunnen nog aangepast worden.');
        redirect('mijn_aanvragen.php');
    }

    $fields = [
        'aanvraag_bewerk_id' => (string) $aanvraagId,
        'aanvrager_voornaam' => bewerkKolom($pdo, $aanvraag, 'aanvrager_voornaam'),
        'aanvrager_naam' => bewerkKolom($pdo, $aanvraag, 'aanvrager_naam'),
        'vak1_tel' => bewerkKolom($pdo, $aanvraag, 'aanvrager_telefoon'),
        'aanvrager_email' => (string) ($aanvraag['eigenaar_email'] ?? ''),
        'vak1_afdeling' => (string) ($aanvraag['afdeling_tekst'] ?? ''),
        'vak1_exzone' => (int) ($aanvraag['ex_zone'] ?? 0) === 1 ? 'ja' : 'neen',
        'vak1_werkbeschrijving' => (string) ($aanvraag['werkbeschrijving'] ?? ''),
        'vak1_foto_data' => bewerkKolom($pdo, $aanvraag, 'vak1_foto_data'),
        'vak2_doel' => bewerkKolom($pdo, $aanvraag, 'vak2_doel') ?: ((int) bewerkKolom($pdo, $aanvraag, 'aanvrager_is_school') === 1 ? 'school' : 'externe'),
        'aanvrager_is_school' => (int) bewerkKolom($pdo, $aanvraag, 'aanvrager_is_school') === 1 ? 'ja' : 'nee',
        'vak2_firma' => bewerkKolom($pdo, $aanvraag, 'firma_naam'),
        'firma_naam' => bewerkKolom($pdo, $aanvraag, 'firma_naam'),
        'uitvoerder_voornaam' => bewerkKolom($pdo, $aanvraag, 'uitvoerder_voornaam'),
        'uitvoerder_naam' => bewerkKolom($pdo, $aanvraag, 'uitvoerder_naam'),
        'vak2_datumwerken' => (string) ($aanvraag['datum_werken'] ?? ''),
        'werktijd_van' => (string) ($aanvraag['werktijd_van'] ?? ''),
        'werktijd_tot' => (string) ($aanvraag['werktijd_tot'] ?? ''),
        'vak2_veiligheidstest' => strtolower((string) ($aanvraag['veiligheidstest_status'] ?? '')),
        'vca' => (int) ($aanvraag['vca_verplicht'] ?? 0) === 1 ? 'ja' : 'nee',
        'geldig_tot' => (string) ($aanvraag['vca_geldig_tot'] ?? ''),
        'vermoedelijke_duur' => (string) ($aanvraag['vermoedelijke_duur'] ?? ''),
        'werkzaamheden' => (string) ($aanvraag['werkzaamheden'] ?? ''),
        'vak3_aandachtspunten' => (string) ($aanvraag['aandachtspunten_vak3'] ?? ''),
        'vak3_parkeerplaats' => bewerkKolom($pdo, $aanvraag, 'vak3_parkeerplaats'),
        'vak4_voornaam' => bewerkKolom($pdo, $aanvraag, 'vak4_voornaam'),
        'vak4_naam' => bewerkKolom($pdo, $aanvraag, 'vak4_naam'),
        'vak4_aandachtspunten' => (string) ($aanvraag['andere_werkzaamheden'] ?? ''),
        'afd_geen' => (int) bewerkKolom($pdo, $aanvraag, 'vak4_geen_andere_werk') === 1 ? '1' : '',
        'preventie_aanvullend' => bewerkKolom($pdo, $aanvraag, 'preventie_aanvullend'),
        'datum_opdrachtgever' => bewerkKolom($pdo, $aanvraag, 'datum_opdrachtgever'),
        'datum_afdeling' => bewerkKolom($pdo, $aanvraag, 'datum_afdeling'),
    ];

    if ($fields['vak2_doel'] === 'school') {
        $fields['vak2_school_uitvoerder'] = 'GTI Beveren';
        $fields['firma_naam'] = 'GTI Beveren';
    }

    $lists = [
        'vak2_act_koud' => [],
        'vak2_act_warm' => [],
        'vak2_vervoer' => [],
        'vak2_stoffen' => [],
        'vak2_chemicalien' => [],
        'vak5_vergunningen' => [],
        'vak5_toelatingen' => [],
        'vak5_preventie' => [],
        'medewerkers' => [],
        'voertuigen_attesten' => [],
    ];

    $linkQueries = [
        'vak2_act_koud' => ['vergunning_activiteit_koud', 'activiteit_koud_id'],
        'vak2_act_warm' => ['vergunning_activiteit_warm', 'activiteit_warm_id'],
        'vak2_vervoer' => ['vergunning_machine', 'machine_id'],
        'vak2_stoffen' => ['vergunning_gevaarlijke_stof', 'gevaarlijke_stof_id'],
        'vak2_chemicalien' => ['vergunning_chemisch_pictogram', 'chemisch_pictogram_id'],
        'vak5_vergunningen' => ['vergunning_andere_vergunning', 'andere_vergunning_id'],
        'vak5_toelatingen' => ['vergunning_toelating', 'toelating_id'],
        'vak5_preventie' => ['vergunning_preventie_item', 'preventie_optie_id'],
    ];

    foreach ($linkQueries as $storageKey => [$table, $column]) {
        if (!databaseTableExists($pdo, $table) || !databaseColumnExists($pdo, $table, $column)) {
            continue;
        }

        $linkStmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE vergunning_id = :id");
        $linkStmt->execute(['id' => $aanvraagId]);
        $lists[$storageKey] = array_map('strval', $linkStmt->fetchAll(PDO::FETCH_COLUMN));
    }

    if (databaseTableExists($pdo, 'vergunning_medewerker')) {
        if (databaseColumnExists($pdo, 'vergunning_medewerker', 'medewerker_id') && databaseTableExists($pdo, 'medewerker')) {
            $medewerkerStmt = $pdo->prepare('
                SELECT m.voornaam, m.achternaam AS naam
                FROM vergunning_medewerker vm
                INNER JOIN medewerker m ON m.id = vm.medewerker_id
                WHERE vm.vergunning_id = :id
                ORDER BY vm.id ASC
            ');
        } else {
            $telefoonSelect = databaseColumnExists($pdo, 'vergunning_medewerker', 'telefoon') ? ', telefoon' : ", '' AS telefoon";
            $medewerkerStmt = $pdo->prepare("
                SELECT voornaam, naam {$telefoonSelect}
                FROM vergunning_medewerker
                WHERE vergunning_id = :id
                ORDER BY id ASC
            ");
        }
        $medewerkerStmt->execute(['id' => $aanvraagId]);
        $lists['medewerkers'] = $medewerkerStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (databaseTableExists($pdo, 'vergunning_voertuig_attest')) {
        $typeSelect = databaseColumnExists($pdo, 'vergunning_voertuig_attest', 'voertuig_type') ? 'voertuig_type' : "'' AS voertuig_type";
        $voertuigStmt = $pdo->prepare("
            SELECT {$typeSelect}, nummerplaat, attest_geldig_tot
            FROM vergunning_voertuig_attest
            WHERE vergunning_id = :id
            ORDER BY id ASC
        ");
        $voertuigStmt->execute(['id' => $aanvraagId]);
        $lists['voertuigen_attesten'] = $voertuigStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $jsonFields = json_encode($fields, $jsonOptions);
    $jsonLists = json_encode($lists, $jsonOptions);
} catch (Throwable $exception) {
    error_log('aanvraag_bewerken failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Aanvraag laden om te bewerken is mislukt.');
    redirect('mijn_aanvragen.php');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aanvraag laden</title>
</head>
<body>
<p>Aanvraag wordt geladen...</p>
<script>
const fields = <?= $jsonFields ?: '{}' ?>;
const lists = <?= $jsonLists ?: '{}' ?>;
const keepKeys = ['aanvraag_session_id'];
const removePrefixes = ['vak', 'visited_', 'aanvrager_', 'uitvoerder_', 'firma', 'medewerkers', 'voertuigen_attesten', 'werktijd_', 'vermoedelijke_duur', 'geldig_tot', 'werkzaamheden', 'vca', 'afd_', 'admin_test'];
const toRemove = [];

for (let index = 0; index < sessionStorage.length; index++) {
    const key = sessionStorage.key(index);
    if (!key || keepKeys.includes(key)) continue;
    if (key === 'aanvraag_bewerk_id' || removePrefixes.some(prefix => key.indexOf(prefix) === 0)) {
        toRemove.push(key);
    }
}

toRemove.forEach(key => sessionStorage.removeItem(key));
sessionStorage.setItem('aanvraag_session_id', String(Date.now()));

Object.keys(fields).forEach(key => {
    sessionStorage.setItem(key, String(fields[key] ?? ''));
});

Object.keys(lists).forEach(key => {
    sessionStorage.setItem(key, JSON.stringify(lists[key] ?? []));
});

['werkvergunning_vak1.php', 'werkvergunning_vak2.php', 'werkvergunning_vak2_activiteiten.php', 'werkvergunning_vak2_chemicalien.php', 'werkvergunning_vak3.php', 'werkvergunning_vak4.php', 'werkvergunning_vak5.php', 'werkvergunning_preventie.php'].forEach(page => {
    sessionStorage.setItem('visited_' + page, 'true');
});

window.location.href = '../PHP/werkvergunning_vak1.php';
</script>
</body>
</html>
