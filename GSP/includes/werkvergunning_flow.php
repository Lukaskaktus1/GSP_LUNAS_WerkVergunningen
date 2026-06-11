<?php

declare(strict_types=1);

function refreshSessionProfileFromDatabase(PDO $pdo, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    try {
        $userFallbackSelect = '';
        $userFallbackColumns = optionalTableColumns($pdo, 'users', ['voornaam', 'naam', 'telefoon']);
        $profileExtraColumns = [];

        if (databaseTableExists($pdo, 'user_profiel')) {
            $profileExtraColumns = optionalTableColumns($pdo, 'user_profiel', ['klas', 'vak']);
        }

        if ($userFallbackColumns !== []) {
            $parts = [];
            foreach ($userFallbackColumns as $column) {
                $parts[] = "u.{$column} AS users_{$column}";
            }
            $userFallbackSelect = ', ' . implode(', ', $parts);
        }

        if (databaseTableExists($pdo, 'user_profiel') && databaseColumnExists($pdo, 'user_profiel', 'user_id')) {
            $lastNameColumn = databaseColumnExists($pdo, 'user_profiel', 'achternaam') ? 'achternaam' : 'naam';
            $stmt = $pdo->prepare("
                SELECT
                    u.email,
                    p.voornaam AS profiel_voornaam,
                    p.{$lastNameColumn} AS profiel_naam,
                    p.telefoon AS profiel_telefoon
                    " . ($profileExtraColumns !== [] ? ', p.' . implode(', p.', $profileExtraColumns) : '') . "
                    {$userFallbackSelect}
                FROM users u
                LEFT JOIN user_profiel p ON p.user_id = u.id
                WHERE u.id = :id
                LIMIT 1
            ");
        } else {
            $select = ['u.email'];
            foreach ($userFallbackColumns as $column) {
                $select[] = "u.{$column}";
            }
            $stmt = $pdo->prepare(sprintf(
                'SELECT %s FROM users u WHERE u.id = :id LIMIT 1',
                implode(', ', $select)
            ));
        }

        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return;
        }

        $voornaam = trim((string) ($row['profiel_voornaam'] ?? $row['users_voornaam'] ?? $row['voornaam'] ?? ''));
        $naam = trim((string) ($row['profiel_naam'] ?? $row['users_naam'] ?? $row['naam'] ?? ''));
        $telefoon = trim((string) ($row['profiel_telefoon'] ?? $row['users_telefoon'] ?? $row['telefoon'] ?? ''));

        if ($voornaam !== '') {
            $_SESSION['voornaam'] = $voornaam;
        }
        if ($naam !== '') {
            $_SESSION['naam'] = $naam;
        }
        if ($telefoon !== '') {
            $_SESSION['telefoon'] = $telefoon;
        }
        if (!empty($row['email'])) {
            $_SESSION['email'] = (string) $row['email'];
        }
        foreach (['klas', 'vak'] as $extraProfileField) {
            if (!empty($row[$extraProfileField])) {
                $_SESSION[$extraProfileField] = (string) $row[$extraProfileField];
            }
        }
    } catch (Throwable $exception) {
        error_log('refreshSessionProfileFromDatabase failed: ' . $exception->getMessage());
    }
}

function haalGekoppeldeWaardenSafe(PDO $pdo, string $sql, int $vergunningId, string $tableHint = ''): array
{
    if ($tableHint !== '' && !databaseTableExists($pdo, $tableHint)) {
        return [];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['vergunning_id' => $vergunningId]);

        return array_values(array_filter(
            array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
            static fn (string $waarde): bool => $waarde !== ''
        ));
    } catch (Throwable $exception) {
        error_log('haalGekoppeldeWaardenSafe failed (' . $tableHint . '): ' . $exception->getMessage());

        return [];
    }
}

function vergunningStatusLabel(string $status): string
{
    return match ($status) {
        'concept' => 'Concept',
        'ingediend' => 'Ingediend',
        'in_beoordeling' => 'In beoordeling',
        'goedgekeurd' => 'Goedgekeurd',
        'afgekeurd' => 'Afgekeurd',
        'in_uitvoering' => 'In uitvoering',
        'vak_vi_voltooid' => 'Vak VI voltooid',
        'afgerond' => 'Afgerond',
        'afgemeld' => 'Afgemeld',
        'gesloten' => 'Gesloten',
        default => 'Onbekend',
    };
}

function vergunningStatusClass(string $status): string
{
    return match ($status) {
        'goedgekeurd', 'vak_vi_voltooid', 'afgerond' => 'status-goedgekeurd',
        'afgekeurd' => 'status-afgekeurd',
        'ingediend', 'in_beoordeling', 'in_uitvoering' => 'status-wachtend',
        default => 'status-concept',
    };
}

function ensureWerkvergunningStatusEnum(PDO $pdo): void
{
    if (!databaseColumnExists($pdo, 'werkvergunning', 'status')) {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'status'
        LIMIT 1
    ");
    $stmt->execute();

    if ((string) $stmt->fetchColumn() !== 'enum') {
        return;
    }

    $pdo->exec("
        ALTER TABLE werkvergunning
        MODIFY status ENUM(
            'concept',
            'ingediend',
            'in_beoordeling',
            'goedgekeurd',
            'afgekeurd',
            'in_uitvoering',
            'vak_vi_voltooid',
            'afgerond',
            'afgemeld',
            'gesloten'
        ) NOT NULL DEFAULT 'concept'
    ");
}

function herstelVakViVoltooidStatusIndienNodig(PDO $pdo, array $aanvraag, int $userId): array
{
    $status = trim((string) ($aanvraag['status'] ?? ''));

    if (!in_array($status, ['', '0', 'onbekend', 'in_uitvoering'], true)) {
        return $aanvraag;
    }

    $vergunningId = (int) ($aanvraag['id'] ?? 0);

    if ($vergunningId <= 0 || (int) ($aanvraag['eigenaar_user_id'] ?? 0) !== $userId) {
        return $aanvraag;
    }

    $vak6Volledig = alleVak6DagenVolledig($pdo, $vergunningId, $aanvraag);

    if (!$vak6Volledig && $status === 'in_uitvoering') {
        try {
            $update = $pdo->prepare("UPDATE werkvergunning SET status = 'goedgekeurd' WHERE id = :id");
            $update->execute(['id' => $vergunningId]);
            $aanvraag['status'] = 'goedgekeurd';
        } catch (Throwable $exception) {
            error_log('herstelVakViVoltooidStatusIndienNodig legacy reset failed: ' . $exception->getMessage());
        }

        return $aanvraag;
    }

    if (!$vak6Volledig) {
        return $aanvraag;
    }

    try {
        ensureWerkvergunningStatusEnum($pdo);
        $update = $pdo->prepare("UPDATE werkvergunning SET status = 'vak_vi_voltooid' WHERE id = :id");
        $update->execute(['id' => $vergunningId]);
        $aanvraag['status'] = 'vak_vi_voltooid';
    } catch (Throwable $exception) {
        error_log('herstelVakViVoltooidStatusIndienNodig failed: ' . $exception->getMessage());
    }

    return $aanvraag;
}

function magVergunningBekijken(array $aanvraag, int $userId, string $role): bool
{
    if (in_array($role, ['directeur', 'ta', 'admin'], true)) {
        return true;
    }

    if ($role === 'leerkracht') {
        if ((int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId) {
            return true;
        }

        try {
            $pdo = getDbConnection();
            return (string) ($aanvraag['vak2_doel'] ?? '') === 'school'
                && leerkrachtMagKlasBeheren($pdo, $userId, (string) ($aanvraag['vak2_klas'] ?? ''));
        } catch (Throwable $exception) {
            error_log('magVergunningBekijken teacher check failed: ' . $exception->getMessage());
        }
    }

    return (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId;
}

function magVergunningKeuren(PDO $pdo, array $aanvraag, int $userId, string $role): bool
{
    if (in_array($role, ['directeur', 'ta', 'admin'], true)) {
        return true;
    }

    return $role === 'leerkracht'
        && (string) ($aanvraag['vak2_doel'] ?? '') === 'school'
        && leerkrachtMagKlasBeheren($pdo, $userId, (string) ($aanvraag['vak2_klas'] ?? ''));
}

function ensureWerkvergunningBeoordelingTable(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS werkvergunning_beoordeling (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vergunning_id INT NOT NULL,
            beoordelaar_user_id INT NOT NULL,
            beoordelaar_rol VARCHAR(40) NOT NULL,
            actie VARCHAR(40) NOT NULL,
            naam VARCHAR(150) NULL,
            handtekening LONGTEXT NULL,
            opmerking TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_werkvergunning_beoordeling_vergunning (vergunning_id),
            CONSTRAINT fk_werkvergunning_beoordeling_vergunning
                FOREIGN KEY (vergunning_id) REFERENCES werkvergunning(id)
                ON DELETE CASCADE
        )
    SQL);
}

function magVergunningBewerken(array $aanvraag, int $userId): bool
{
    return (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId
        && (string) ($aanvraag['status'] ?? '') === 'ingediend';
}

function magVergunningVak6(array $aanvraag, int $userId): bool
{
    return (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId
        && (string) ($aanvraag['status'] ?? '') === 'goedgekeurd';
}

function magVergunningVak7(array $aanvraag, int $userId, PDO $pdo): bool
{
    $aanvraag = herstelVakViVoltooidStatusIndienNodig($pdo, $aanvraag, $userId);

    return (int) ($aanvraag['eigenaar_user_id'] ?? 0) === $userId
        && (string) ($aanvraag['status'] ?? '') === 'vak_vi_voltooid'
        && alleVak6DagenVolledig($pdo, (int) ($aanvraag['id'] ?? 0), $aanvraag);
}

function parseAantalWerkdagen(?string $vermoedelijkeDuur, int $default = 1): int
{
    $default = max(1, $default);
    $tekst = strtolower(trim((string) $vermoedelijkeDuur));

    if ($tekst === '') {
        return $default;
    }

    if (preg_match('/(\d+)\s*(dagen|dag|d\b)/', $tekst, $matches) === 1) {
        return max(1, min(31, (int) $matches[1]));
    }

    return $default;
}

/** @return list<string> Datums als Y-m-d */
function berekenWerkdagenVoorVergunning(array $aanvraag): array
{
    $startRaw = trim((string) ($aanvraag['datum_werken'] ?? ''));

    if ($startRaw === '') {
        return [date('Y-m-d')];
    }

    try {
        $start = new DateTimeImmutable($startRaw);
    } catch (Throwable) {
        return [date('Y-m-d')];
    }

    $eindRaw = trim((string) (
        $aanvraag['datum_tot'] ?? $aanvraag['einddatum'] ?? $aanvraag['tot_datum'] ?? ''
    ));

    if ($eindRaw !== '') {
        try {
            $eind = new DateTimeImmutable($eindRaw);
            if ($eind >= $start) {
                $verschil = (int) $start->diff($eind)->days;
                $aantalDagen = max(1, min(31, $verschil + 1));
            } else {
                $aantalDagen = 1;
            }
        } catch (Throwable) {
            $aantalDagen = parseAantalWerkdagen((string) ($aanvraag['vermoedelijke_duur'] ?? ''), 1);
        }
    } else {
        $aantalDagen = parseAantalWerkdagen((string) ($aanvraag['vermoedelijke_duur'] ?? ''), 1);
    }

    $dagen = [];

    for ($i = 0; $i < $aantalDagen; $i++) {
        $dagen[] = $start->modify('+' . $i . ' day')->format('Y-m-d');
    }

    return $dagen;
}

function initialiseerVak6Logs(PDO $pdo, int $vergunningId, array $aanvraag): void
{
    if (!databaseTableExists($pdo, 'werkvergunning_vak6_log')) {
        return;
    }

    $dagen = berekenWerkdagenVoorVergunning($aanvraag);
    $eersteDag = $dagen[0] ?? date('Y-m-d');
    $stmt = $pdo->prepare('
        INSERT IGNORE INTO werkvergunning_vak6_log (vergunning_id, log_datum, dag_label)
        VALUES (:vergunning_id, :log_datum, :dag_label)
    ');

    $stmt->execute([
        'vergunning_id' => $vergunningId,
        'log_datum' => $eersteDag,
        'dag_label' => 'Dag 1',
    ]);
}

function ensureWerkvergunningVak6LogTable(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS werkvergunning_vak6_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vergunning_id INT NOT NULL,
            log_datum DATE NOT NULL,
            dag_label VARCHAR(40) NULL,
            van_tijd VARCHAR(20) NULL,
            tot_tijd VARCHAR(20) NULL,
            afdeling_naam VARCHAR(150) NULL,
            afdeling_paraaf VARCHAR(150) NULL,
            uitvoerder_naam VARCHAR(150) NULL,
            uitvoerder_paraaf VARCHAR(150) NULL,
            aantal_uitvoerders INT NULL,
            overdracht_handtekening VARCHAR(255) NULL,
            is_volledig TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_werkvergunning_vak6_log_datum (vergunning_id, log_datum),
            INDEX idx_werkvergunning_vak6_log_vergunning (vergunning_id),
            CONSTRAINT fk_werkvergunning_vak6_log_vergunning
                FOREIGN KEY (vergunning_id) REFERENCES werkvergunning(id)
                ON DELETE CASCADE
        )
    SQL);
}

/** @return array<string, array<string, mixed>> */
function laadVak6Logs(PDO $pdo, int $vergunningId): array
{
    if (!databaseTableExists($pdo, 'werkvergunning_vak6_log')) {
        return [];
    }

    $stmt = $pdo->prepare('
        SELECT *
        FROM werkvergunning_vak6_log
        WHERE vergunning_id = :vergunning_id
        ORDER BY log_datum ASC
    ');
    $stmt->execute(['vergunning_id' => $vergunningId]);
    $logs = [];

    foreach ($stmt->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }
        $logs[(string) $row['log_datum']] = $row;
    }

    return $logs;
}

function alleVak6DagenVolledig(PDO $pdo, int $vergunningId, array $aanvraag): bool
{
    if (!databaseTableExists($pdo, 'werkvergunning_vak6_log')) {
        return false;
    }

    $verwachteDagen = berekenWerkdagenVoorVergunning($aanvraag);

    if ($verwachteDagen === []) {
        return false;
    }

    $stmt = $pdo->prepare('
        SELECT COUNT(*) AS totaal,
               SUM(CASE WHEN is_volledig = 1 THEN 1 ELSE 0 END) AS volledig
        FROM werkvergunning_vak6_log
        WHERE vergunning_id = :vergunning_id
    ');
    $stmt->execute(['vergunning_id' => $vergunningId]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        return false;
    }

    return (int) ($row['totaal'] ?? 0) >= count($verwachteDagen)
        && (int) ($row['volledig'] ?? 0) >= count($verwachteDagen);
}

function vak6LogIsVolledig(array $log): bool
{
    foreach (['van_tijd', 'tot_tijd', 'afdeling_naam', 'uitvoerder_naam'] as $veld) {
        if (trim((string) ($log[$veld] ?? '')) === '') {
            return false;
        }
    }

    return true;
}
