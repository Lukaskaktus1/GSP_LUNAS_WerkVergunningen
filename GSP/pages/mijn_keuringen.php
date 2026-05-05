<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['directeur', 'ta', 'admin']);

$pdo = getDbConnection();

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, eigenaar_email, eigenaar_rol, werkbeschrijving, datum_werken, status, updated_at
    FROM werkvergunning
    WHERE status IN ('goedgekeurd', 'afgekeurd')
    ORDER BY updated_at DESC
");

$stmt->execute();
$aanvragen = $stmt->fetchAll();

function statusClassKeuringHistoriek(string $status): string
{
    return match ($status) {
        'goedgekeurd' => 'status-goedgekeurd',
        'afgekeurd' => 'status-afgekeurd',
        default => 'status-onbekend',
    };
}

function statusLabelKeuringHistoriek(string $status): string
{
    return match ($status) {
        'goedgekeurd' => 'Goedgekeurd',
        'afgekeurd' => 'Afgekeurd',
        default => 'Onbekend',
    };
}

function terugNaarOverzichtKeuringen(): string
{
    $role = (string) ($_SESSION['rol'] ?? '');

    return match ($role) {
        'ta' => 'overzicht_ta.php',
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
    <title>Mijn keuringen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .keuringen-table {
            width: 100%;
            border-collapse: collapse;
        }

        .keuringen-table th,
        .keuringen-table td {
            padding: 12px;
            text-align: left;
            border-top: 1px solid #ddd;
            vertical-align: middle;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-goedgekeurd {
            background: #dcfce7;
            color: #166534;
        }

        .status-afgekeurd {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-onbekend {
            background: #e5e7eb;
            color: #374151;
        }

        .small-btn {
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .open-btn {
            background: #e0f2fe;
            color: #075985;
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
            <h1>Mijn keuringen</h1>
            <p>
                Welkom,
                <span class="role-badge">
                    <i class="fas fa-user"></i>
                    <?= e(getCurrentUserRoleLabel()) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='<?= e(terugNaarOverzichtKeuringen()) ?>'">
            <i class="fas fa-arrow-left"></i>
            <span>Terug</span>
        </button>

        <button class="logout-btn" onclick="window.location.href='keuringen.php'">
            <i class="fas fa-check-circle"></i>
            <span>Openstaande keuringen</span>
        </button>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Uitloggen</span>
        </button>
    </div>
</header>

<main class="main-container">
    <section class="applications-section">
        <h2 class="section-title">Gekeurde aanvragen</h2>

        <div class="applications-container">
            <?php if (empty($aanvragen)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="empty-state-text">Er zijn nog geen gekeurde aanvragen.</div>
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
                                <th>Status</th>
                                <th>Laatst aangepast</th>
                                <th>Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aanvragen as $aanvraag): ?>
                                <?php $status = (string) $aanvraag['status']; ?>
                                <tr>
                                    <td><?= e((string) $aanvraag['vergunning_nummer']) ?></td>
                                    <td><?= e((string) ($aanvraag['eigenaar_email'] ?? '')) ?></td>
                                    <td><?= e((string) ($aanvraag['eigenaar_rol'] ?? '')) ?></td>
                                    <td><?= e((string) $aanvraag['werkbeschrijving']) ?></td>
                                    <td><?= e((string) ($aanvraag['datum_werken'] ?? '')) ?></td>
                                    <td>
                                        <span class="status-badge <?= e(statusClassKeuringHistoriek($status)) ?>">
                                            <?= e(statusLabelKeuringHistoriek($status)) ?>
                                        </span>
                                    </td>
                                    <td><?= e((string) $aanvraag['updated_at']) ?></td>
                                    <td>
                                        <button
                                            class="small-btn open-btn"
                                            onclick="window.location.href='aanvraag_bekijken.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                        >
                                            Meer info
                                        </button>
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

<script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>