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

    if ($werkbeschrijving === null) {
        $pdo->rollBack();
        setFlashMessage('error', 'Werkbeschrijving is verplicht.');
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

        // Voorlopige mapping naar hoofdtabel werkvergunning
        // Later kunnen activiteiten/machines/preventie apart naar koppeltabellen.
        'werkzaamheden' => fieldValue($fields, 'vak2_naam'),
        'aandachtspunten_vak3' => fieldValue($fields, 'vak3_aandachtspunten'),
        'andere_werkzaamheden' => fieldValue($fields, 'vak4_aandachtspunten'),
        'naam_afdelingsverantwoordelijke' => fieldValue($fields, 'vak4_naam'),

        'afdeling_tekst' =>
            fieldValue($fields, 'vak1_afdeling')
            ?? fieldValue($fields, 'vak4_afdeling'),

        'datum_werken' => fieldValue($fields, 'vak2_datumwerken'),

        // Deze velden worden voorlopig nog niet duidelijk uit de flow gehaald.
        'werktijd_van' => null,
        'werktijd_tot' => null,
        'vermoedelijke_duur' => null,

        'ex_zone' => boolFromText(fieldValue($fields, 'vak1_exzone')),
        'veiligheidstest_status' => fieldValue($fields, 'vak2_veiligheidstest') ?: 'NVT',

        // VCA wordt later eventueel uitgebreider gekoppeld.
        'vca_verplicht' => 0,
        'vca_geldig_tot' => null,

        // LOTO behoort niet meer tot het pakket.
        'loto_verplicht' => 0,
        'loto_status' => 'niet_van_toepassing',

        'status' => 'ingediend',
    ]);

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