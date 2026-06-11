<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['directeur', 'ta', 'admin']);

$pdo = getDbConnection();

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, eigenaar_email, eigenaar_rol, werkbeschrijving, datum_werken, status, updated_at
    FROM werkvergunning
    WHERE status IN ('goedgekeurd', 'afgekeurd', 'vak_vi_voltooid', 'afgerond')
    ORDER BY updated_at DESC
");

$stmt->execute();
$aanvragen = $stmt->fetchAll();
$flash = getFlashMessage();

function statusClassKeuringHistoriek(string $status): string
{
    return vergunningStatusClass($status);
}

function statusLabelKeuringHistoriek(string $status): string
{
    return vergunningStatusLabel($status);
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
    <link rel="icon" type="image/png" sizes="32x32" href="../IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="../IMAGES/favicon-180.png?v=20260609">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="gsp-role-label" content="<?= e(getCurrentUserRoleLabel()) ?>">
    <title>Mijn keuringen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/mijn_keuringen.css?v=20260608">
    
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
    <link rel="stylesheet" href="../CSS/user-menu.css?v=20260609">
    <?= gspInlineCss(['overzicht.css', 'mijn_keuringen.css', 'local-icons.css', 'user-menu.css']) ?>
    <?= gspOverviewCriticalCss() ?>
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
                    <?= e(currentUserDisplayName()) ?>
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
            <?= flashDialogMarkup($flash) ?>
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

<script src="../JS/ui-feedback.js"></script>
<script src="../JS/user-menu.js?v=20260609"></script>
</body>
</html>
