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

$signatures = $data['signatures'] ?? [];

if (!is_array($signatures)) {
    $signatures = [];
}

function fieldValue(array $fields, string $key): ?string
{
    $value = trim((string) ($fields[$key] ?? ''));
    return $value === '' ? null : $value;
}

function signatureValue(array $signatures, string $key): ?string
{
    $value = trim((string) ($signatures[$key] ?? ''));
    return $value === '' ? null : $value;
}

function firstFieldValue(array $fields, array $keys): ?string
{
    foreach ($keys as $key) {
        $value = fieldValue($fields, $key);

        if ($value !== null) {
            return $value;
        }
    }

    return null;
}

function normalizeAanvraagFields(array $fields): array
{
    $aliases = [
        'vak1_afdeling' => ['vak1_afdeling', 'afdeling_tekst', 'afdeling', 'vak4_afdeling'],
        'vak1_exzone' => ['vak1_exzone', 'ex_zone', 'exzone', 'vak1_exzone_ja', 'vak1_exzone_neen'],
        'vak1_werkbeschrijving' => ['vak1_werkbeschrijving', 'werkbeschrijving', 'beschrijving'],
        'vak2_datumwerken' => ['vak2_datumwerken', 'datum_werken', 'datumwerken'],
        'vak2_veiligheidstest' => ['vak2_veiligheidstest', 'veiligheidstest_status', 'veiligheidstest', 'vak2_veiligheidstest_ok', 'vak2_veiligheidstest_nok'],
        'vca' => ['vca', 'vca_verplicht', 'vca_ja', 'vca_nee'],
        'geldig_tot' => ['geldig_tot', 'vca_geldig_tot'],
        'vak2_firma' => ['vak2_firma', 'firma_naam'],
        'firma_naam' => ['firma_naam', 'vak2_firma'],
    ];

    foreach ($aliases as $canonicalKey => $keys) {
        if (fieldValue($fields, $canonicalKey) !== null) {
            continue;
        }

        $value = firstFieldValue($fields, $keys);

        if ($value !== null) {
            $fields[$canonicalKey] = $value;
        }
    }

    if (fieldValue($fields, 'vak2_doel') === 'school') {
        $fields['aanvrager_is_school'] = 'ja';
        $fields['firma_naam'] = 'GTI Beveren';
        $fields['vak2_school_uitvoerder'] = 'GTI Beveren';
    } elseif (fieldValue($fields, 'vak2_doel') === 'externe') {
        $fields['aanvrager_is_school'] = 'nee';
    }

    return $fields;
}

$fields = normalizeAanvraagFields($fields);

function boolFromText(?string $value): int
{
    $value = strtolower(trim((string) $value));

    return in_array($value, ['ja', 'yes', '1', 'true', 'aan'], true) ? 1 : 0;
}

function veiligheidstestStatus(?string $value): string
{
    $value = strtoupper(trim((string) $value));

    return in_array($value, ['OK', 'NOK'], true) ? $value : 'NVT';
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

function canSaveReferenceLinks(PDO $pdo, string $referenceTable, string $linkTable): bool
{
    return databaseTableExists($pdo, $referenceTable)
        && databaseTableExists($pdo, $linkTable);
}

function extraFieldValue(array $fields, array $mapping, int $id, string $column): ?string
{
    $key = $mapping[$id][$column] ?? null;

    if (!is_string($key) || $key === '') {
        return null;
    }

    return fieldValue($fields, $key);
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

    $werkbeschrijving = fieldValue($fields, 'vak1_werkbeschrijving');

    $requiredFieldLabels = [
        'vak1_afdeling' => 'Afdeling',
        'vak1_exzone' => 'EX-zone',
        'vak1_werkbeschrijving' => 'Werkbeschrijving',
        'uitvoerder_voornaam' => 'Voornaam uitvoerder',
        'uitvoerder_naam' => 'Naam uitvoerder',
        'vak2_datumwerken' => 'Datum werken',
        'werktijd_van' => 'Werktijd van',
        'werktijd_tot' => 'Werktijd tot',
        'vermoedelijke_duur' => 'Vermoedelijke duur',
        'werkzaamheden' => 'Werkzaamheden',
        'vak2_veiligheidstest' => 'Veiligheidstest',
        'vca' => 'VCA',
    ];
    $requiredFieldPages = [
        'vak1_afdeling' => '../PHP/werkvergunning_vak1.php',
        'vak1_exzone' => '../PHP/werkvergunning_vak1.php',
        'vak1_werkbeschrijving' => '../PHP/werkvergunning_vak1.php',
        'uitvoerder_voornaam' => '../PHP/werkvergunning_vak2.php',
        'uitvoerder_naam' => '../PHP/werkvergunning_vak2.php',
        'vak2_datumwerken' => '../PHP/werkvergunning_vak2.php',
        'werktijd_van' => '../PHP/werkvergunning_vak2.php',
        'werktijd_tot' => '../PHP/werkvergunning_vak2.php',
        'vermoedelijke_duur' => '../PHP/werkvergunning_vak2.php',
        'werkzaamheden' => '../PHP/werkvergunning_vak2.php',
        'vak2_veiligheidstest' => '../PHP/werkvergunning_vak2.php',
        'vca' => '../PHP/werkvergunning_vak2.php',
    ];

    foreach ($requiredFieldLabels as $key => $label) {
        if (fieldValue($fields, $key) === null) {
            $pdo->rollBack();
            setFlashMessage('error', $label . ' is verplicht.');
            redirect($requiredFieldPages[$key] ?? '../PHP/werkvergunning_vak1.php');
        }
    }

    $vak2Doel = fieldValue($fields, 'vak2_doel');
    $isSchool = $vak2Doel === 'school'
        || fieldValue($fields, 'aanvrager_is_school') === 'ja';

    if ($vak2Doel === null && fieldValue($fields, 'aanvrager_is_school') === null) {
        $pdo->rollBack();
        setFlashMessage('error', 'Kies wie de werken uitvoert.');
        redirect('../PHP/werkvergunning_vak2.php');
    }

    if (!$isSchool && fieldValue($fields, 'vak2_firma') === null && fieldValue($fields, 'firma_naam') === null) {
        $pdo->rollBack();
        setFlashMessage('error', 'Firma is verplicht wanneer een externe firma de werken uitvoert.');
        redirect('../PHP/werkvergunning_vak2.php');
    }

    if (fieldValue($fields, 'vca') === 'ja' && fieldValue($fields, 'geldig_tot') === null) {
        $pdo->rollBack();
        setFlashMessage('error', 'Geldig tot is verplicht wanneer VCA vereist is.');
        redirect('../PHP/werkvergunning_vak2.php');
    }

    $coreValues = [
        'werkbeschrijving' => $werkbeschrijving,
        'werkzaamheden' => fieldValue($fields, 'werkzaamheden'),
        'aandachtspunten_vak3' => fieldValue($fields, 'vak3_aandachtspunten'),
        'andere_werkzaamheden' => (function () use ($fields): ?string {
            if (fieldValue($fields, 'afd_geen') === '1') {
                return null;
            }

            return fieldValue($fields, 'vak4_aandachtspunten');
        })(),
        'naam_afdelingsverantwoordelijke' => trim(implode(' ', array_filter([
            fieldValue($fields, 'vak4_voornaam') ?? '',
            fieldValue($fields, 'vak4_naam') ?? '',
        ]))) ?: null,

        'afdeling_tekst' =>
            fieldValue($fields, 'vak1_afdeling')
            ?? fieldValue($fields, 'vak4_afdeling'),

        'datum_werken' => fieldValue($fields, 'vak2_datumwerken'),

        'werktijd_van' => fieldValue($fields, 'werktijd_van'),
        'werktijd_tot' => fieldValue($fields, 'werktijd_tot'),
        'vermoedelijke_duur' => fieldValue($fields, 'vermoedelijke_duur'),

        'ex_zone' => boolFromText(fieldValue($fields, 'vak1_exzone')),
        'veiligheidstest_status' => veiligheidstestStatus(fieldValue($fields, 'vak2_veiligheidstest')),

        'vca_verplicht' => boolFromText(fieldValue($fields, 'vca')),
        'vca_geldig_tot' => fieldValue($fields, 'geldig_tot'),

        // LOTO behoort niet meer tot het pakket.
        'loto_verplicht' => 0,
        'loto_status' => 'niet_van_toepassing',
        'status' => 'ingediend',
    ];

    $bewerkingId = filter_var(fieldValue($fields, 'aanvraag_bewerk_id'), FILTER_VALIDATE_INT);
    $vergunningId = 0;

    if ($bewerkingId !== false && $bewerkingId > 0) {
        $checkEdit = $pdo->prepare('
            SELECT id, eigenaar_user_id, status
            FROM werkvergunning
            WHERE id = :id
            LIMIT 1
        ');
        $checkEdit->execute(['id' => $bewerkingId]);
        $bestaandeAanvraag = $checkEdit->fetch();

        if (!is_array($bestaandeAanvraag)
            || (int) ($bestaandeAanvraag['eigenaar_user_id'] ?? 0) !== (int) $_SESSION['user_id']
            || (string) ($bestaandeAanvraag['status'] ?? '') !== 'ingediend'
        ) {
            $pdo->rollBack();
            setFlashMessage('error', 'Deze aanvraag kan niet meer aangepast worden.');
            redirect('mijn_aanvragen.php');
        }

        $updateStmt = $pdo->prepare('
            UPDATE werkvergunning
            SET werkbeschrijving = :werkbeschrijving,
                werkzaamheden = :werkzaamheden,
                aandachtspunten_vak3 = :aandachtspunten_vak3,
                andere_werkzaamheden = :andere_werkzaamheden,
                naam_afdelingsverantwoordelijke = :naam_afdelingsverantwoordelijke,
                afdeling_tekst = :afdeling_tekst,
                datum_werken = :datum_werken,
                werktijd_van = :werktijd_van,
                werktijd_tot = :werktijd_tot,
                vermoedelijke_duur = :vermoedelijke_duur,
                ex_zone = :ex_zone,
                veiligheidstest_status = :veiligheidstest_status,
                vca_verplicht = :vca_verplicht,
                vca_geldig_tot = :vca_geldig_tot,
                loto_verplicht = :loto_verplicht,
                loto_status = :loto_status,
                status = :status
            WHERE id = :id
        ');
        $updateStmt->execute($coreValues + ['id' => $bewerkingId]);
        $vergunningId = (int) $bewerkingId;

        foreach ([
            'vergunning_medewerker',
            'vergunning_voertuig_attest',
            'vergunning_activiteit_koud',
            'vergunning_activiteit_warm',
            'vergunning_machine',
            'vergunning_gevaarlijke_stof',
            'vergunning_chemisch_pictogram',
            'vergunning_andere_vergunning',
            'vergunning_toelating',
            'vergunning_preventie_item',
        ] as $linkTable) {
            if (!databaseTableExists($pdo, $linkTable) || !databaseColumnExists($pdo, $linkTable, 'vergunning_id')) {
                continue;
            }

            $deleteLinks = $pdo->prepare("DELETE FROM {$linkTable} WHERE vergunning_id = :id");
            $deleteLinks->execute(['id' => $vergunningId]);
        }
    } else {
        $vergunningNummer = generateVergunningNummer($pdo);

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

        $stmt->execute($coreValues + [
            'vergunning_nummer' => $vergunningNummer,
            'eigenaar_user_id' => (int) $_SESSION['user_id'],
            'eigenaar_email' => (string) ($_SESSION['email'] ?? ''),
            'eigenaar_rol' => (string) ($_SESSION['rol'] ?? ''),
        ]);

        $vergunningId = (int) $pdo->lastInsertId();
    }

    if ($vergunningId <= 0) {
        throw new RuntimeException('Vergunning-ID kon niet worden bepaald.');
    }

    $aanvragerVoornaam = fieldValue($fields, 'aanvrager_voornaam') ?? (string) ($_SESSION['voornaam'] ?? '');
    $aanvragerNaam = fieldValue($fields, 'aanvrager_naam') ?? (string) ($_SESSION['naam'] ?? '');
    $firmaNaam = $isSchool
        ? 'GTI Beveren'
        : (fieldValue($fields, 'vak2_firma') ?? fieldValue($fields, 'firma_naam'));

    $optionalUpdateValues = [
        'aanvrager_voornaam' => $aanvragerVoornaam,
        'aanvrager_naam' => $aanvragerNaam,
        'aanvrager_telefoon' => fieldValue($fields, 'vak1_tel') ?? (string) ($_SESSION['telefoon'] ?? ''),
        'aanvrager_is_school' => $isSchool ? 1 : 0,
        'firma_naam' => $firmaNaam,
        'uitvoerder_voornaam' => fieldValue($fields, 'uitvoerder_voornaam'),
        'uitvoerder_naam' => fieldValue($fields, 'uitvoerder_naam'),
        'vak2_doel' => $vak2Doel ?? ($isSchool ? 'school' : 'externe'),
        'vak2_klas' => null,
        'vak1_foto_data' => fieldValue($fields, 'vak1_foto_data'),
        'vak3_parkeerplaats' => fieldValue($fields, 'vak3_parkeerplaats'),
        'vak4_voornaam' => fieldValue($fields, 'vak4_voornaam'),
        'vak4_naam' => fieldValue($fields, 'vak4_naam'),
        'vak4_geen_andere_werk' => fieldValue($fields, 'afd_geen') === '1' ? 1 : 0,
        'preventie_aanvullend' => fieldValue($fields, 'preventie_aanvullend'),
        'handtekening_opdrachtgever' => signatureValue($signatures, 'handtekening_opdrachtgever'),
        'datum_opdrachtgever' => fieldValue($fields, 'datum_opdrachtgever'),
        'handtekening_afdeling' => signatureValue($signatures, 'handtekening_afdeling'),
        'datum_afdeling' => fieldValue($fields, 'datum_afdeling'),
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
        $gebruikKoppelTabel = databaseColumnExists($pdo, 'vergunning_medewerker', 'medewerker_id')
            && databaseTableExists($pdo, 'medewerker');

        $medewerkerZoekStmt = null;
        $medewerkerMaakStmt = null;
        $medewerkerKoppelStmt = null;
        $medewerkerVrijStmt = null;

        if ($gebruikKoppelTabel) {
            $medewerkerZoekStmt = $pdo->prepare('
                SELECT id
                FROM medewerker
                WHERE voornaam = :voornaam
                  AND achternaam = :achternaam
                LIMIT 1
            ');
            $medewerkerMaakStmt = $pdo->prepare('
                INSERT INTO medewerker (voornaam, achternaam)
                VALUES (:voornaam, :achternaam)
            ');
            $medewerkerKoppelStmt = $pdo->prepare('
                INSERT INTO vergunning_medewerker (vergunning_id, medewerker_id)
                VALUES (:vergunning_id, :medewerker_id)
            ');
        } elseif (
            databaseColumnExists($pdo, 'vergunning_medewerker', 'voornaam')
            && databaseColumnExists($pdo, 'vergunning_medewerker', 'naam')
        ) {
            $kolommen = ['vergunning_id', 'voornaam', 'naam'];
            $waarden = [':vergunning_id', ':voornaam', ':naam'];

            if (databaseColumnExists($pdo, 'vergunning_medewerker', 'telefoon')) {
                $kolommen[] = 'telefoon';
                $waarden[] = ':telefoon';
            }

            $medewerkerVrijStmt = $pdo->prepare(
                'INSERT INTO vergunning_medewerker (' . implode(', ', $kolommen) . ') VALUES (' . implode(', ', $waarden) . ')'
            );
        }

        foreach (listValues($lists, 'medewerkers') as $medewerker) {
            if (!is_array($medewerker)) {
                continue;
            }

            $voornaam = trim((string) ($medewerker['voornaam'] ?? ''));
            $naam = trim((string) ($medewerker['naam'] ?? ''));

            if ($voornaam === '' && $naam === '') {
                continue;
            }

            if ($gebruikKoppelTabel && $medewerkerZoekStmt && $medewerkerMaakStmt && $medewerkerKoppelStmt) {
                $medewerkerZoekStmt->execute([
                    'voornaam' => $voornaam,
                    'achternaam' => $naam,
                ]);
                $medewerkerId = (int) $medewerkerZoekStmt->fetchColumn();

                if ($medewerkerId <= 0) {
                    $medewerkerMaakStmt->execute([
                        'voornaam' => $voornaam,
                        'achternaam' => $naam,
                    ]);
                    $medewerkerId = (int) $pdo->lastInsertId();
                }

                if ($medewerkerId > 0) {
                    $medewerkerKoppelStmt->execute([
                        'vergunning_id' => $vergunningId,
                        'medewerker_id' => $medewerkerId,
                    ]);
                }
            } elseif ($medewerkerVrijStmt) {
                $params = [
                    'vergunning_id' => $vergunningId,
                    'voornaam' => $voornaam,
                    'naam' => $naam,
                ];

                if (databaseColumnExists($pdo, 'vergunning_medewerker', 'telefoon')) {
                    $params['telefoon'] = trim((string) ($medewerker['telefoon'] ?? ''));
                }

                $medewerkerVrijStmt->execute($params);
            }
        }
    }

    if (databaseTableExists($pdo, 'vergunning_voertuig_attest')) {
        $voertuigColumns = ['vergunning_id', 'nummerplaat', 'attest_geldig_tot'];
        $voertuigValues = [':vergunning_id', ':nummerplaat', ':attest_geldig_tot'];

        if (databaseColumnExists($pdo, 'vergunning_voertuig_attest', 'voertuig_type')) {
            $voertuigColumns[] = 'voertuig_type';
            $voertuigValues[] = ':voertuig_type';
        }

        $voertuigStmt = $pdo->prepare(
            'INSERT INTO vergunning_voertuig_attest (' . implode(', ', $voertuigColumns) . ') VALUES (' . implode(', ', $voertuigValues) . ')'
        );

        foreach (listValues($lists, 'voertuigen_attesten') as $voertuig) {
            if (!is_array($voertuig)) {
                continue;
            }

            $nummerplaat = trim((string) ($voertuig['nummerplaat'] ?? ''));
            $attestGeldigTot = trim((string) ($voertuig['attest_geldig_tot'] ?? ''));
            $voertuigType = trim((string) ($voertuig['voertuig_type'] ?? ''));

            if ($nummerplaat === '' && $attestGeldigTot === '' && $voertuigType === '') {
                continue;
            }

            if ($nummerplaat === '' || $voertuigType === '') {
                $pdo->rollBack();
                setFlashMessage('error', 'Vul per voertuig het voertuigtype en de nummerplaat in.');
                redirect('../PHP/werkvergunning_vak2_activiteiten.php');
            }

            $voertuigParams = [
                'vergunning_id' => $vergunningId,
                'nummerplaat' => $nummerplaat,
                'attest_geldig_tot' => $attestGeldigTot === '' ? null : $attestGeldigTot,
            ];

            if (in_array(':voertuig_type', $voertuigValues, true)) {
                $voertuigParams['voertuig_type'] = $voertuigType;
            }

            $voertuigStmt->execute($voertuigParams);
        }
    }

    if (canSaveReferenceLinks($pdo, 'activiteit_koud', 'vergunning_activiteit_koud')) {
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
    }

    if (canSaveReferenceLinks($pdo, 'activiteit_warm', 'vergunning_activiteit_warm')) {
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
    }

    if (canSaveReferenceLinks($pdo, 'machine', 'vergunning_machine')) {
        $machineIds = existingReferenceIds(
            $pdo,
            'machine',
            selectedIds($lists, 'vak2_vervoer')
        );
        $machineExtraFields = [
            13 => ['extra_info' => 'vervoer_andere_tekst'],
        ];

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
            :extra_info
        )
SQL);

        foreach ($machineIds as $id) {
            $stmtMachine->execute([
                'vergunning_id' => $vergunningId,
                'machine_id' => $id,
                'extra_info' => extraFieldValue($fields, $machineExtraFields, $id, 'extra_info'),
            ]);
        }
    }

    if (canSaveReferenceLinks($pdo, 'gevaarlijke_stof', 'vergunning_gevaarlijke_stof')) {
        $stoffenIds = existingReferenceIds(
            $pdo,
            'gevaarlijke_stof',
            selectedIds($lists, 'vak2_stoffen')
        );
        $stofExtraFields = [
            9 => ['extra_info' => 'stoffen_andere_tekst'],
        ];

        $stmtStof = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_gevaarlijke_stof (
            vergunning_id,
            gevaarlijke_stof_id,
            extra_info
        ) VALUES (
            :vergunning_id,
            :gevaarlijke_stof_id,
            :extra_info
        )
SQL);

        foreach ($stoffenIds as $id) {
            $stmtStof->execute([
                'vergunning_id' => $vergunningId,
                'gevaarlijke_stof_id' => $id,
                'extra_info' => extraFieldValue($fields, $stofExtraFields, $id, 'extra_info'),
            ]);
        }
    }

    if (canSaveReferenceLinks($pdo, 'chemisch_pictogram', 'vergunning_chemisch_pictogram')) {
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
    }

    if (canSaveReferenceLinks($pdo, 'andere_vergunning', 'vergunning_andere_vergunning')) {
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
    }

    if (canSaveReferenceLinks($pdo, 'toelating', 'vergunning_toelating')) {
        $toelatingIds = existingReferenceIds(
            $pdo,
            'toelating',
            selectedIds($lists, 'vak5_toelatingen')
        );
        $toelatingExtraFields = [
            8 => ['vrije_tekst' => 'toel_andere_tekst'],
        ];

        $stmtToelating = $pdo->prepare(<<<'SQL'
        INSERT INTO vergunning_toelating (
            vergunning_id,
            toelating_id,
            vrije_tekst
        ) VALUES (
            :vergunning_id,
            :toelating_id,
            :vrije_tekst
        )
SQL);

        foreach ($toelatingIds as $id) {
            $stmtToelating->execute([
                'vergunning_id' => $vergunningId,
                'toelating_id' => $id,
                'vrije_tekst' => extraFieldValue($fields, $toelatingExtraFields, $id, 'vrije_tekst'),
            ]);
        }
    }

    if (canSaveReferenceLinks($pdo, 'preventie_optie', 'vergunning_preventie_item')) {
        $preventieIds = existingReferenceIds(
            $pdo,
            'preventie_optie',
            selectedIds($lists, 'vak5_preventie')
        );
        $preventieExtraFields = [
            4 => ['extra_tekst' => 'huid_andere_tekst'],
            9 => ['extra_tekst' => 'ogen_andere_tekst'],
            10 => ['extra_korte_tekst' => 'hand_handschoenen_tekst'],
            14 => ['extra_tekst' => 'hand_andere_tekst'],
            17 => ['extra_korte_tekst' => 'adem_halfgelaatsmasker_tekst'],
            18 => ['extra_korte_tekst' => 'adem_volgelaatsmasker_tekst'],
            24 => ['extra_datum' => 'vallen_gekeurd_tekst'],
            26 => ['extra_tekst' => 'vallen_andere_tekst'],
            33 => ['extra_tekst' => 'comm_andere_tekst'],
            34 => ['extra_korte_tekst' => 'andere_handgraven_tekst'],
            38 => ['extra_tekst' => 'andere_andere_tekst'],
            42 => ['extra_tekst' => 'milieu_afval_tekst'],
            45 => ['extra_tekst' => 'milieu_andere_tekst'],
        ];

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
            :extra_tekst,
            :extra_datum,
            :extra_korte_tekst
        )
SQL);

        foreach ($preventieIds as $id) {
            $stmtPreventie->execute([
                'vergunning_id' => $vergunningId,
                'preventie_optie_id' => $id,
                'extra_tekst' => extraFieldValue($fields, $preventieExtraFields, $id, 'extra_tekst'),
                'extra_datum' => extraFieldValue($fields, $preventieExtraFields, $id, 'extra_datum'),
                'extra_korte_tekst' => extraFieldValue($fields, $preventieExtraFields, $id, 'extra_korte_tekst'),
            ]);
        }
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
