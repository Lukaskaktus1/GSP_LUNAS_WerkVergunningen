<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['directeur', 'ta', 'admin']);

$pdo = getDbConnection();

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, eigenaar_email, eigenaar_rol, werkbeschrijving, datum_werken, status, created_at
    FROM werkvergunning
    WHERE status IN ('ingediend', 'in_beoordeling')
    ORDER BY created_at ASC
");

$stmt->execute();
$aanvragen = $stmt->fetchAll();

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
            background: #fef3c7;
            color: #92400e;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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

        .approve-btn {
            background: #dcfce7;
            color: #166534;
        }

        .reject-btn {
            background: #fee2e2;
            color: #991b1b;
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
            <h1>Keuringen</h1>
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

                                            <form action="aanvraag_keuren.php" method="POST" onsubmit="return confirm('Weet u zeker dat u deze aanvraag wilt goedkeuren?');">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <input type="hidden" name="actie" value="goedkeuren">
                                                <button type="submit" class="small-btn approve-btn">
                                                    Goedkeuren
                                                </button>
                                            </form>

                                            <form action="aanvraag_keuren.php" method="POST" onsubmit="return confirm('Weet u zeker dat u deze aanvraag wilt afkeuren?');">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <input type="hidden" name="actie" value="afkeuren">
                                                <button type="submit" class="small-btn reject-btn">
                                                    Afkeuren
                                                </button>
                                            </form>
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

<script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>