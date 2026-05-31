<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mijn_aanvragen.php');
}

$rawData = (string) ($_POST['aanvraag_data'] ?? '');

if ($rawData === '') {
    setFlashMessage('error', 'Geen aanvraaggegevens ontvangen.');
    redirect('mijn_aanvragen.php');
}

$data = json_decode($rawData, true);

if (!is_array($data)) {
    setFlashMessage('error', 'Ongeldige aanvraaggegevens.');
    redirect('mijn_aanvragen.php');
}

$fields = $data['fields'] ?? [];

if (!is_array($fields)) {
    setFlashMessage('error', 'Ongeldige aanvraagvelden.');
    redirect('mijn_aanvragen.php');
}

$lists = $data['lists'] ?? [];

if (!is_array($lists)) {
    $lists = [];
}

function fieldValue(array $fields, string $key): ?string
{
    $value = trim((string) ($fields[$key] ?? ''));
    return $value === '' ? null : $value;
}

function boolFromText(?string $value): int
{
    $value = strtolower(trim((string) $value));

    return in_array($value, ['ja', 'yes', '1', 'true', 'aan'], true) ? 1 : 0;
}

function selectedIds(array $lists, string $key): array
{
    $raw = $lists[$key] ?? [];

    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($raw)) {
        return [];
    }

    $ids = [];

    foreach ($raw as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        if ($id !== false && $id > 0) {
            $ids[] = (int) $id;
        }
    }

    return array_values(array_unique($ids));
}

function listValues(array $lists, string $key): array
{
    $raw = $lists[$key] ?? [];

    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }

    return is_array($raw) ? $raw : [];
}

function existingReferenceIds(PDO $pdo, string $table, array $ids): array
{
    $allowedTables = [
        'activiteit_koud',
        'activiteit_warm',
        'machine',
        'gevaarlijke_stof',
        'chemisch_pictogram',
        'andere_vergunning',
        'toelating',
        'preventie_optie',
    ];

    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Ongeldige referentietabel.');
    }

    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id IN ({$placeholders})");
    $stmt->execute($ids);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function generateVergunningNummer(PDO $pdo): string
{
    $datumKey = date('Ymd');

    $stmt = $pdo->prepare("
        UPDATE vergunning_nummer_counter
        SET laatste_nummer = laatste_nummer + 1
        WHERE id = 1
    ");
    $stmt->execute();

    $selectStmt = $pdo->query("
        SELECT laatste_nummer
        FROM vergunning_nummer_counter
        WHERE id = 1
        LIMIT 1
    ");

    $nummer = (int) $selectStmt->fetchColumn();

    if ($nummer <= 0) {
        throw new RuntimeException('Werkvergunning teller kon niet worden opgehaald.');
    }

    return 'WV-' . $datumKey . '-' . str_pad((string) $nummer, 6, '0', STR_PAD_LEFT);
}

try {
    $pdo = getDbConnection();

    $pdo->beginTransaction();

    $vergunningNummer = generateVergunningNummer($pdo);

    $werkbeschrijving = fieldValue($fields, 'vak1_werkbeschrijving');

    $requiredFieldLabels = [
        'vak1_afdeling' => 'Afdeling',
        'aanvrager_is_school' => 'School of externe firma',
        'vak1_exzone' => 'EX-zone',
        'vak1_werkbeschrijving' => 'Werkbeschrijving',
        'vak2_naam' => 'Verantwoordelijke uitvoerder',
        'vak2_firma' => 'Firma / klas / dienst',
        'vak2_datumwerken' => 'Datum werken',
        'werktijd_van' => 'Werktijd van',
        'werktijd_tot' => 'Werktijd tot',
        'vermoedelijke_duur' => 'Vermoedelijke duur',
        'geldig_tot' => 'Geldig tot',
        'werkzaamheden' => 'Werkzaamheden',
        'vak2_veiligheidstest' => 'Veiligheidstest',
        'vca' => 'VCA',
    ];

    foreach ($requiredFieldLabels as $key => $label) {
        if (fieldValue($fields, $key) === null) {
            $pdo->rollBack();
            setFlashMessage('error', $label . ' is verplicht.');
            redirect('../PHP/werkvergunning_vak1.php');
        }
    }

    if (fieldValue($fields, 'aanvrager_is_school') === 'nee' && fieldValue($fields, 'firma_naam') === null) {
        $pdo->rollBack();
        setFlashMessage('error', 'Firma is verplicht voor externe aanvragen.');
        redirect('../PHP/werkvergunning_vak1.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO werkvergunning (
            vergunning_nummer,
            eigenaar_user_id,
            eigenaar_email,
            eigenaar_rol,
            werkbeschrijving,
            werkzaamheden,
            aandachtspunten_vak3,
            andere_werkzaamheden,
            naam_afdelingsverantwoordelijke,
            afdeling_tekst,
            datum_werken,
            werktijd_van,
            werktijd_tot,
            vermoedelijke_duur,
            ex_zone,
            veiligheidstest_status,
            vca_verplicht,
            vca_geldig_tot,
            loto_verplicht,
            loto_status,
            status
        ) VALUES (
            :vergunning_nummer,
            :eigenaar_user_id,
            :eigenaar_email,
            :eigenaar_rol,
            :werkbeschrijving,
            :werkzaamheden,
            :aandachtspunten_vak3,
            :andere_werkzaamheden,
            :naam_afdelingsverantwoordelijke,
            :afdeling_tekst,
            :datum_werken,
            :werktijd_van,
            :werktijd_tot,
            :vermoedelijke_duur,
            :ex_zone,
            :veiligheidstest_status,
            :vca_verplicht,
            :vca_geldig_tot,
            :loto_verplicht,
            :loto_status,
            :status
        )
    ");

    $stmt->execute([
        'vergunning_nummer' => $vergunningNummer,
        'eigenaar_user_id' => (int) $_SESSION['user_id'],
        'eigenaar_email' => (string) ($_SESSION['email'] ?? ''),
        'eigenaar_rol' => (string) ($_SESSION['rol'] ?? ''),

        'werkbeschrijving' => $werkbeschrijving,

        'werkzaamheden' => fieldValue($fields, 'werkzaamheden') ?? fieldValue($fields, 'vak2_naam'),
        'aandachtspunten_vak3' => fieldValue($fields, 'vak3_aandachtspunten'),
        'andere_werkzaamheden' => fieldValue($fields, 'vak4_aandachtspunten'),
        'naam_afdelingsverantwoordelijke' => fieldValue($fields, 'vak4_naam') ?? fieldValue($fields, 'vak1_naam'),

        'afdeling_tekst' =>
            fieldValue($fields, 'vak1_afdeling')
            ?? fieldValue($fields, 'vak4_afdeling'),

        'datum_werken' => fieldValue($fields, 'vak2_datumwerken'),

        'werktijd_van' => fieldValue($fields, 'werktijd_van'),
        'werktijd_tot' => fieldValue($fields, 'werktijd_tot'),
        'vermoedelijke_duur' => fieldValue($fields, 'vermoedelijke_duur'),

        'ex_zone' => boolFromText(fieldValue($fields, 'vak1_exzone')),
        'veiligheidstest_status' => fieldValue($fields, 'vak2_veiligheidstest') ?: 'NVT',

        'vca_verplicht' => boolFromText(fieldValue($fields, 'vca')),
        'vca_geldig_tot' => fieldValue($fields, 'geldig_tot'),

        // LOTO behoort niet meer tot het pakket.
        'loto_verplicht' => 0,
        'loto_status' => 'niet_van_toepassing',

        'status' => 'ingediend',
    ]);

    $vergunningId = (int) $pdo->lastInsertId();

    if ($vergunningId <= 0) {
        throw new RuntimeException('Vergunning-ID kon niet worden bepaald.');
    }

    $optionalUpdateValues = [
        'aanvrager_voornaam' => (string) ($_SESSION['voornaam'] ?? ''),
        'aanvrager_naam' => (string) ($_SESSION['naam'] ?? ''),
        'aanvrager_telefoon' => (string) ($_SESSION['telefoon'] ?? ''),
        'aanvrager_is_school' => boolFromText(fieldValue($fields, 'aanvrager_is_school')),
        'firma_naam' => fieldValue($fields, 'firma_naam'),
    ];

    $updateParts = [];
    $updateParams = ['id' => $vergunningId];

    foreach ($optionalUpdateValues as $column => $value) {
        if (databaseColumnExists($pdo, 'werkvergunning', $column)) {
            $updateParts[] = "{$column} = :{$column}";
            $updateParams[$column] = $value;
        }
    }

    if ($updateParts !== []) {
        $updateStmt = $pdo->prepare('UPDATE werkvergunning SET ' . implode(', ', $updateParts) . ' WHERE id = :id');
        $updateStmt->execute($updateParams);
    }

    if (databaseTableExists($pdo, 'vergunning_medewerker')) {
        $medewerkerStmt = $pdo->prepare('
            INSERT INTO vergunning_medewerker (vergunning_id, voornaam, naam, telefoon)
            VALUES (:vergunning_id, :voornaam, :naam, :telefoon)
        ');

        foreach (listValues($lists, 'medewerkers') as $medewerker) {
            if (!is_array($medewerker)) {
                continue;
            }

            $voornaam = trim((string) ($medewerker['voornaam'] ?? ''));
            $naam = trim((string) ($medewerker['naam'] ?? ''));

            if ($voornaam === '' && $naam === '') {
                continue;
            }

            $medewerkerStmt->execute([
                'vergunning_id' => $vergunningId,
                'voornaam' => $voornaam,
                'naam' => $naam,
                'telefoon' => trim((string) ($medewerker['telefoon'] ?? '')),
            ]);
        }
    }

    if (databaseTableExists($pdo, 'vergunning_voertuig_attest')) {
        $voertuigStmt = $pdo->prepare('
            INSERT INTO vergunning_voertuig_attest (vergunning_id, nummerplaat, attest_geldig_tot)
            VALUES (:vergunning_id, :nummerplaat, :attest_geldig_tot)
        ');

        foreach (listValues($lists, 'voertuigen_attesten') as $voertuig) {
            if (!is_array($voertuig)) {
                continue;
            }

            $nummerplaat = trim((string) ($voertuig['nummerplaat'] ?? ''));
            $attestGeldigTot = trim((string) ($voertuig['attest_geldig_tot'] ?? ''));

            if ($nummerplaat === '' && $attestGeldigTot === '') {
                continue;
            }

            $voertuigStmt->execute([
                'vergunning_id' => $vergunningId,
                'nummerplaat' => $nummerplaat,
                'attest_geldig_tot' => $attestGeldigTot === '' ? null : $attestGeldigTot,
            ]);
        }
    }

    $koudeIds = existingReferenceIds(
        $pdo,
        'activiteit_koud',
        selectedIds($lists, 'vak2_act_koud')
    );

    $stmtKoud = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_activiteit_koud (vergunning_id, activiteit_koud_id)
        VALUES (:vergunning_id, :activiteit_id)
SQL);

    foreach ($koudeIds as $id) {
        $stmtKoud->execute([
            'vergunning_id' => $vergunningId,
            'activiteit_id' => $id,
        ]);
    }

    $warmeIds = existingReferenceIds(
        $pdo,
        'activiteit_warm',
        selectedIds($lists, 'vak2_act_warm')
    );

    $stmtWarm = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_activiteit_warm (vergunning_id, activiteit_warm_id)
        VALUES (:vergunning_id, :activiteit_id)
SQL);

    foreach ($warmeIds as $id) {
        $stmtWarm->execute([
            'vergunning_id' => $vergunningId,
            'activiteit_id' => $id,
        ]);
    }

    $machineIds = existingReferenceIds(
        $pdo,
        'machine',
        selectedIds($lists, 'vak2_vervoer')
    );

    $stmtMachine = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_machine (
            vergunning_id,
            machine_id,
            attest_geldig_tot,
            extra_info
        ) VALUES (
            :vergunning_id,
            :machine_id,
            NULL,
            NULL
        )
SQL);

    foreach ($machineIds as $id) {
        $stmtMachine->execute([
            'vergunning_id' => $vergunningId,
            'machine_id' => $id,
        ]);
    }

    $stoffenIds = existingReferenceIds(
        $pdo,
        'gevaarlijke_stof',
        selectedIds($lists, 'vak2_stoffen')
    );

    $stmtStof = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_gevaarlijke_stof (
            vergunning_id,
            gevaarlijke_stof_id,
            extra_info
        ) VALUES (
            :vergunning_id,
            :gevaarlijke_stof_id,
            NULL
        )
SQL);

    foreach ($stoffenIds as $id) {
        $stmtStof->execute([
            'vergunning_id' => $vergunningId,
            'gevaarlijke_stof_id' => $id,
        ]);
    }

    $pictogramIds = existingReferenceIds(
        $pdo,
        'chemisch_pictogram',
        selectedIds($lists, 'vak2_chemicalien')
    );

    $stmtPictogram = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_chemisch_pictogram (vergunning_id, chemisch_pictogram_id)
        VALUES (:vergunning_id, :pictogram_id)
SQL);

    foreach ($pictogramIds as $id) {
        $stmtPictogram->execute([
            'vergunning_id' => $vergunningId,
            'pictogram_id' => $id,
        ]);
    }

    $andereVergunningIds = existingReferenceIds(
        $pdo,
        'andere_vergunning',
        selectedIds($lists, 'vak5_vergunningen')
    );

    $stmtAndereVergunning = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_andere_vergunning (vergunning_id, andere_vergunning_id)
        VALUES (:vergunning_id, :andere_vergunning_id)
SQL);

    foreach ($andereVergunningIds as $id) {
        $stmtAndereVergunning->execute([
            'vergunning_id' => $vergunningId,
            'andere_vergunning_id' => $id,
        ]);
    }

    $toelatingIds = existingReferenceIds(
        $pdo,
        'toelating',
        selectedIds($lists, 'vak5_toelatingen')
    );

    $stmtToelating = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_toelating (
            vergunning_id,
            toelating_id,
            vrije_tekst
        ) VALUES (
            :vergunning_id,
            :toelating_id,
            NULL
        )
SQL);

    foreach ($toelatingIds as $id) {
        $stmtToelating->execute([
            'vergunning_id' => $vergunningId,
            'toelating_id' => $id,
        ]);
    }

    $preventieIds = existingReferenceIds(
        $pdo,
        'preventie_optie',
        selectedIds($lists, 'vak5_preventie')
    );

    $stmtPreventie = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_preventie_item (
            vergunning_id,
            preventie_optie_id,
            aangevinkt,
            extra_tekst,
            extra_datum,
            extra_korte_tekst
        ) VALUES (
            :vergunning_id,
            :preventie_optie_id,
            1,
            NULL,
            NULL,
            NULL
        )
SQL);

    foreach ($preventieIds as $id) {
        $stmtPreventie->execute([
            'vergunning_id' => $vergunningId,
            'preventie_optie_id' => $id,
        ]);
    }

    $pdo->commit();

    setFlashMessage('success', 'Werkvergunning succesvol ingediend.');
    redirect('mijn_aanvragen.php?submitted=1');

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Aanvraag opslaan failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Aanvraag opslaan is mislukt.');
    redirect('mijn_aanvragen.php');
}
