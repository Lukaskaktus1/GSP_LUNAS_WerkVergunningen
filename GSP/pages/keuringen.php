<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['leerkracht', 'directeur', 'ta', 'admin']);

$pdo = getDbConnection();
$role = (string) ($_SESSION['rol'] ?? '');
$userId = (int) ($_SESSION['user_id'] ?? 0);

$params = [];
$where = "status IN ('ingediend', 'in_beoordeling')";
$hasVak2Klas = databaseColumnExists($pdo, 'werkvergunning', 'vak2_klas');

if ($role === 'leerkracht') {
    if (!$hasVak2Klas) {
        $where .= ' AND 1 = 0';
    } else {
        $klassen = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => normalizeKlasNaam((string) ($row['klas'] ?? '')),
            userKlasVakProfielen($pdo, $userId)
        ))));

        if ($klassen === []) {
            $where .= ' AND 1 = 0';
        } else {
            $placeholders = [];
            foreach ($klassen as $index => $klas) {
                $key = 'klas_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $klas;
            }
            $where .= " AND vak2_doel = 'school' AND LOWER(COALESCE(vak2_klas, '')) IN (" . implode(', ', $placeholders) . ')';
        }
    }
}

$vak2KlasSelect = $hasVak2Klas ? 'vak2_klas' : "NULL AS vak2_klas";

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, eigenaar_email, eigenaar_rol, werkbeschrijving, datum_werken, {$vak2KlasSelect}, status, created_at
    FROM werkvergunning
    WHERE {$where}
    ORDER BY created_at ASC
");

$stmt->execute($params);
$aanvragen = $stmt->fetchAll();
$flash = getFlashMessage();

function statusLabelKeuring(string $status): string
{
    return match ($status) {
        'ingediend' => 'Ingediend',
        'in_beoordeling' => 'In beoordeling',
        default => 'Onbekend',
    };
}

function terugNaarOverzicht(): string
{
    $role = (string) ($_SESSION['rol'] ?? '');

    return match ($role) {
        'ta' => 'overzicht_ta.php',
        'leerkracht' => 'overzicht_leerkracht.php',
        'directeur' => 'overzicht_directeur.php',
        'admin' => 'overzicht_admin.php',
        default => 'overzicht_leerling.php',
    };
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuringen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="../CSS/keuringen.css">
    <link rel="stylesheet" href="../CSS/local-icons.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="far fa-file-lines"></i>
        </div>
        <div class="header-title">
            <h1>Keuringen</h1>
            <p>
                Welkom,
                <span class="role-badge">
                    <i class="fas fa-user"></i>
                    <?= e(currentUserDisplayName()) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='<?= e(terugNaarOverzicht()) ?>'">
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
        <h2 class="section-title">Openstaande aanvragen</h2>

        <div class="applications-container">
            <?= flashDialogMarkup($flash) ?>
            <?php if (empty($aanvragen)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="empty-state-text">Er zijn momenteel geen openstaande aanvragen om te keuren.</div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="keuringen-table">
                        <thead>
                            <tr>
                                <th>Nummer</th>
                                <th>Aanvrager</th>
                                <th>Rol</th>
                                <th>Werkbeschrijving</th>
                                <th>Datum werken</th>
                                <th>Klas</th>
                                <th>Status</th>
                                <th>Aangemaakt</th>
                                <th>Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aanvragen as $aanvraag): ?>
                                <tr>
                                    <td><?= e((string) $aanvraag['vergunning_nummer']) ?></td>
                                    <td><?= e((string) ($aanvraag['eigenaar_email'] ?? '')) ?></td>
                                    <td><?= e((string) ($aanvraag['eigenaar_rol'] ?? '')) ?></td>
                                    <td><?= e((string) $aanvraag['werkbeschrijving']) ?></td>
                                    <td><?= e((string) ($aanvraag['datum_werken'] ?? '')) ?></td>
                                    <td><?= e((string) ($aanvraag['vak2_klas'] ?? '')) ?></td>
                                    <td>
                                        <span class="status-badge">
                                            <?= e(statusLabelKeuring((string) $aanvraag['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= e((string) $aanvraag['created_at']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button
                                                class="small-btn open-btn"
                                                onclick="window.location.href='aanvraag_bekijken.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Bekijken
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<script src="../JS/ui-feedback.js"></script>
</body>
</html>
