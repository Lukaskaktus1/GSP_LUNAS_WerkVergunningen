<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';
requireRole(['admin']);

$reviewNotification = latestReviewNotification(getDbConnection());
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="../IMAGES/favicon-32.png?v=20260609">
    <link rel="apple-touch-icon" sizes="180x180" href="../IMAGES/favicon-180.png?v=20260609">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin overzicht - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/overzicht_admin.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
    <?= gspInlineCss(['overzicht.css', 'overzicht_admin.css', 'local-icons.css']) ?>
    <?= gspOverviewCriticalCss() ?>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="far fa-file-lines"></i>
        </div>
        <div class="header-title">
            <h1>Werkvergunning Portaal</h1>
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
        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Uitloggen</span>
        </button>
    </div>
</header>

<main class="main-container">
    <section class="quick-actions-section">
        <h2 class="section-title">Admin acties</h2>

        <div class="quick-actions">
            <div class="action-card highlighted" onclick="window.location.href='admin_gebruikers.php'">
                <div class="action-card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="action-card-title">Gebruikers beheren</div>
                <div class="action-card-subtitle">Rollen en accounts aanpassen</div>
            </div>

            <div class="action-card test-action" onclick="window.location.href='../PHP/werkvergunning_vak1.php?new=1&test=1'">
                <div class="action-card-icon">
                    <i class="fas fa-vial"></i>
                </div>
                <div class="action-card-title">Testaanvraag</div>
                <div class="action-card-subtitle">Doorloop zonder verplichte velden of database-opslag</div>
            </div>

            <div class="action-card" onclick="window.location.href='keuringen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="action-card-title">Keuringen</div>
                <div class="action-card-subtitle">Openstaande aanvragen bekijken</div>
            </div>

            <div class="action-card" onclick="window.location.href='mijn_keuringen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="action-card-title">Mijn keuringen</div>
                <div class="action-card-subtitle">Bekijk gekeurde aanvragen</div>
            </div>

            <div class="action-card" onclick="window.location.href='mijn_aanvragen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="action-card-title">Mijn aanvragen</div>
                <div class="action-card-subtitle">Eigen aanvragen bekijken</div>
            </div>
            <div class="action-card" onclick="window.location.href='contact.php'">
                <div class="action-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="action-card-title">Contact</div>
                <div class="action-card-subtitle">Neem contact op</div>
            </div>
        </div>
    </section>

    <section class="applications-section">
        <h2 class="section-title">Lokale testaanvragen</h2>

        <div class="applications-container" id="admin_test_aanvragen">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-vial"></i>
                </div>
                <div class="empty-state-text">
                    Nog geen lokale testaanvragen op dit toestel.
                </div>
            </div>
        </div>
    </section>
</main>

<?= reviewNotificationMarkup($reviewNotification) ?>
<script src="../JS/ui-feedback.js"></script>
<script src="../JS/admin-test-aanvragen.js"></script>
</body>
</html>
