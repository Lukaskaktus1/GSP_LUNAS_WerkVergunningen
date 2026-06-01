<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, vergunning_nummer, werkbeschrijving, datum_werken, status, created_at
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
    return match ($status) {
        'goedgekeurd' => 'status-goedgekeurd',
        'afgekeurd' => 'status-afgekeurd',
        'ingediend', 'in_beoordeling' => 'status-wachtend',
        default => 'status-concept',
    };
}

function statusLabel(string $status): string
{
    return match ($status) {
        'concept' => 'Concept',
        'ingediend' => 'Ingediend',
        'in_beoordeling' => 'In beoordeling',
        'goedgekeurd' => 'Goedgekeurd',
        'afgekeurd' => 'Afgekeurd',
        'afgemeld' => 'Afgemeld',
        'gesloten' => 'Gesloten',
        default => 'Onbekend',
    };
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn aanvragen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="../CSS/mijn_aanvragen.css">
    <link rel="stylesheet" href="../CSS/local-icons.css">
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
                    <?= e(getCurrentUserRoleLabel()) ?>
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
                            $status = (string) $aanvraag['status'];
                            $magVerwijderen = !in_array($status, ['goedgekeurd', 'gesloten'], true);
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
                                            Openen
                                        </button>

                                        <?php if ($magVerwijderen): ?>
                                            <form action="aanvraag_verwijderen.php" method="POST" data-confirm-title="Aanvraag verwijderen" data-confirm-message="Weet u zeker dat u deze aanvraag wilt verwijderen?" data-confirm-solution="Verwijder alleen aanvragen die niet meer nodig zijn. Goedgekeurde of gesloten aanvragen blijven beschermd.">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <button type="submit" class="small-btn delete-btn">
                                                    Verwijderen
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
