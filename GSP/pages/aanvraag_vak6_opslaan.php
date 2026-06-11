<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mijn_aanvragen.php');
}

$vergunningId = filter_input(INPUT_POST, 'vergunning_id', FILTER_VALIDATE_INT);
$logDatum = trim((string) ($_POST['log_datum'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$vergunningId) {
    setFlashMessage('error', 'Ongeldige loggegevens.');
    redirect('mijn_aanvragen.php');
}

try {
    $pdo = getDbConnection();

    ensureWerkvergunningVak6LogTable($pdo);

    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $vergunningId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag) || !magVergunningVak6($aanvraag, $userId)) {
        setFlashMessage('error', 'U mag dit logboek nu niet bewerken.');
        redirect('mijn_aanvragen.php');
    }

    $rawLogs = $_POST['logs'] ?? null;
    $bulkMode = is_array($rawLogs);

    if (!$bulkMode) {
        if ($logDatum === '') {
            setFlashMessage('error', 'Ongeldige loggegevens.');
            redirect('mijn_aanvragen.php');
        }

        $rawLogs = [[
            'log_datum' => $logDatum,
            'van_tijd' => $_POST['van_tijd'] ?? '',
            'tot_tijd' => $_POST['tot_tijd'] ?? '',
            'afdeling_naam' => $_POST['afdeling_naam'] ?? '',
            'afdeling_paraaf' => $_POST['afdeling_paraaf'] ?? '',
            'uitvoerder_naam' => $_POST['uitvoerder_naam'] ?? '',
            'uitvoerder_paraaf' => $_POST['uitvoerder_paraaf'] ?? '',
            'aantal_uitvoerders' => $_POST['aantal_uitvoerders'] ?? '',
            'overdracht_handtekening' => $_POST['overdracht_handtekening'] ?? '',
        ]];
    }

    $upsert = $pdo->prepare('
        INSERT INTO werkvergunning_vak6_log (
            vergunning_id,
            log_datum,
            dag_label,
            van_tijd,
            tot_tijd,
            afdeling_naam,
            afdeling_paraaf,
            uitvoerder_naam,
            uitvoerder_paraaf,
            aantal_uitvoerders,
            overdracht_handtekening,
            is_volledig
        ) VALUES (
            :vergunning_id,
            :log_datum,
            :dag_label,
            :van_tijd,
            :tot_tijd,
            :afdeling_naam,
            :afdeling_paraaf,
            :uitvoerder_naam,
            :uitvoerder_paraaf,
            :aantal_uitvoerders,
            :overdracht_handtekening,
            1
        )
        ON DUPLICATE KEY UPDATE
            dag_label = VALUES(dag_label),
            van_tijd = VALUES(van_tijd),
            tot_tijd = VALUES(tot_tijd),
            afdeling_naam = VALUES(afdeling_naam),
            afdeling_paraaf = VALUES(afdeling_paraaf),
            uitvoerder_naam = VALUES(uitvoerder_naam),
            uitvoerder_paraaf = VALUES(uitvoerder_paraaf),
            aantal_uitvoerders = VALUES(aantal_uitvoerders),
            overdracht_handtekening = VALUES(overdracht_handtekening),
            is_volledig = 1
    ');

    $opgeslagen = 0;
    $opgeslagenDatums = [];

    foreach (array_values($rawLogs) as $index => $rawLog) {
        if (!is_array($rawLog)) {
            continue;
        }

        $log = [
            'log_datum' => trim((string) ($rawLog['log_datum'] ?? '')),
            'van_tijd' => trim((string) ($rawLog['van_tijd'] ?? '')),
            'tot_tijd' => trim((string) ($rawLog['tot_tijd'] ?? '')),
            'afdeling_naam' => trim((string) ($rawLog['afdeling_naam'] ?? '')),
            'afdeling_paraaf' => trim((string) ($rawLog['afdeling_paraaf'] ?? '')),
            'uitvoerder_naam' => trim((string) ($rawLog['uitvoerder_naam'] ?? '')),
            'uitvoerder_paraaf' => trim((string) ($rawLog['uitvoerder_paraaf'] ?? '')),
            'aantal_uitvoerders' => trim((string) ($rawLog['aantal_uitvoerders'] ?? '')),
            'overdracht_handtekening' => trim((string) ($rawLog['overdracht_handtekening'] ?? '')),
        ];

        if ($log['log_datum'] === '') {
            setFlashMessage('error', 'Elke Vak VI-rij moet een datum hebben.');
            redirect('aanvraag_bekijken.php?id=' . $vergunningId);
        }

        if (!vak6LogIsVolledig($log)) {
            setFlashMessage('error', 'Vul minstens datum, van/tot, afdeling en uitvoerder in voor elke Vak VI-rij.');
            redirect('aanvraag_bekijken.php?id=' . $vergunningId);
        }

        $upsert->execute([
            'vergunning_id' => $vergunningId,
            'log_datum' => $log['log_datum'],
            'dag_label' => 'Dag ' . ($index + 1),
            'van_tijd' => $log['van_tijd'],
            'tot_tijd' => $log['tot_tijd'],
            'afdeling_naam' => $log['afdeling_naam'],
            'afdeling_paraaf' => $log['afdeling_paraaf'],
            'uitvoerder_naam' => $log['uitvoerder_naam'],
            'uitvoerder_paraaf' => $log['uitvoerder_paraaf'],
            'aantal_uitvoerders' => $log['aantal_uitvoerders'] === '' ? null : (int) $log['aantal_uitvoerders'],
            'overdracht_handtekening' => $log['overdracht_handtekening'],
        ]);
        $opgeslagen++;
        $opgeslagenDatums[] = $log['log_datum'];
    }

    if ($opgeslagen === 0) {
        setFlashMessage('error', 'Er werd geen Vak VI-rij gevonden om op te slaan.');
        redirect('aanvraag_bekijken.php?id=' . $vergunningId);
    }

    if ($bulkMode) {
        $opgeslagenDatums = array_values(array_unique($opgeslagenDatums));
        $placeholders = implode(',', array_fill(0, count($opgeslagenDatums), '?'));
        $deleteParams = array_merge([$vergunningId], $opgeslagenDatums);
        $delete = $pdo->prepare("
            DELETE FROM werkvergunning_vak6_log
            WHERE vergunning_id = ?
              AND log_datum NOT IN ({$placeholders})
        ");
        $delete->execute($deleteParams);
    }

    setFlashMessage('success', 'Vak VI-logboek is opgeslagen.');

    if (alleVak6DagenVolledig($pdo, $vergunningId, $aanvraag)) {
        if (databaseColumnExists($pdo, 'werkvergunning', 'status')) {
            ensureWerkvergunningStatusEnum($pdo);
            $statusUpdate = $pdo->prepare("
                UPDATE werkvergunning
                SET status = 'vak_vi_voltooid'
                WHERE id = :id
                  AND status NOT IN ('afgekeurd', 'vak_vi_voltooid', 'afgerond', 'afgemeld', 'gesloten')
            ");
            $statusUpdate->execute(['id' => $vergunningId]);
        }

        setFlashMessage('success', 'Alle Vak VI-dagen zijn ingevuld. U kunt nu Vak VII openen.');
    }

    redirect((string) ($_POST['return_to'] ?? 'aanvraag_bekijken.php?id=' . $vergunningId));
} catch (Throwable $exception) {
    error_log('aanvraag_vak6_opslaan failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Opslaan van Vak VI is mislukt.');
    redirect('aanvraag_bekijken.php?id=' . (int) $vergunningId);
}
