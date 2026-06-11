<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, eigenaar_user_id, werkbeschrijving, datum_werken, vermoedelijke_duur, status, created_at
    FROM werkvergunning
    WHERE eigenaar_user_id = :user_id
    ORDER BY created_at DESC
");

$stmt->execute([
    'user_id' => $userId,
]);

$aanvragen = $stmt->fetchAll();
$flash = getFlashMessage();


function statusClass(string $status): string
{
    return vergunningStatusClass($status);
}

function statusLabel(string $status): string
{
    return vergunningStatusLabel($status);
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
    <title>Mijn aanvragen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/mijn_aanvragen.css?v=20260608">
    
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
    <link rel="stylesheet" href="../CSS/user-menu.css?v=20260609">
    <?= gspInlineCss(['overzicht.css', 'mijn_aanvragen.css', 'local-icons.css', 'user-menu.css']) ?>
    <?= gspOverviewCriticalCss() ?>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="far fa-file-lines"></i>
        </div>
        <div class="header-title">
            <h1>Mijn aanvragen</h1>
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
        <button class="logout-btn" onclick="window.location.href='<?php
            $role = (string) ($_SESSION['rol'] ?? '');
            echo match ($role) {
                'leerling' => 'overzicht_leerling.php',
                'leerkracht' => 'overzicht_leerkracht.php',
                'ta' => 'overzicht_ta.php',
                'directeur' => 'overzicht_directeur.php',
                'admin' => 'overzicht_admin.php',
                default => 'overzicht_leerling.php',
            };
        ?>'">
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
        <h2 class="section-title">Uw aanvragen</h2>

        <?= flashDialogMarkup($flash) ?>

        <div class="applications-container">
            <?php if (empty($aanvragen)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="empty-state-text">Nog geen aanvragen ingediend</div>

                    <button class="empty-state-button" onclick="window.location.href='../PHP/werkvergunning_vak1.php?new=1'">
                        Start uw eerste aanvraag
                    </button>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="aanvragen-table">
                        <thead>
                        <tr>
                            <th>Nummer</th>
                            <th>Werkbeschrijving</th>
                            <th>Datum werken</th>
                            <th>Status</th>
                            <th>Aangemaakt</th>
                            <th>Acties</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aanvragen as $aanvraag): ?>
                            <?php
                            $aanvraag = herstelVakViVoltooidStatusIndienNodig($pdo, $aanvraag, $userId);
                            $status = (string) $aanvraag['status'];
                            $magAanpassen = $status === 'ingediend';
                            $magVak6 = $status === 'goedgekeurd';
                            $magVak7 = $status === 'vak_vi_voltooid';
                            ?>
                            <tr>
                                <td><?= e((string) $aanvraag['vergunning_nummer']) ?></td>
                                <td><?= e((string) $aanvraag['werkbeschrijving']) ?></td>
                                <td><?= e((string) ($aanvraag['datum_werken'] ?? '')) ?></td>
                                <td>
                                    <span class="status-badge <?= e(statusClass($status)) ?>">
                                        <?= e(statusLabel($status)) ?>
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

                                        <?php if ($magAanpassen): ?>
                                            <button
                                                class="small-btn"
                                                type="button"
                                                onclick="window.location.href='aanvraag_bewerken.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Aanpassen
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($magVak6): ?>
                                            <button
                                                class="small-btn"
                                                type="button"
                                                onclick="window.location.href='aanvraag_vak6.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Vak VI invullen
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($magVak7): ?>
                                            <button
                                                class="small-btn"
                                                type="button"
                                                onclick="window.location.href='aanvraag_vak7.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Vak VII invullen
                                            </button>
                                        <?php endif; ?>

                                        <form action="aanvraag_verwijderen.php" method="POST" data-confirm-title="Aanvraag verwijderen" data-confirm-message="Wilt u deze aanvraag verwijderen?" data-confirm-solution="Deze actie verwijdert de aanvraag uit uw overzicht.">
                                            <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                            <button type="submit" class="small-btn delete-btn">
                                                Verwijderen
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

<script src="../JS/ui-feedback.js"></script>
<script src="../JS/user-menu.js?v=20260609"></script>
</body>
</html>
