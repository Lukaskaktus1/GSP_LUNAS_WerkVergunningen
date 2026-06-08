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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn aanvragen - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/mijn_aanvragen.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
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
                            $status = (string) $aanvraag['status'];
                            $magActieAanvragen = !in_array($status, ['gesloten', 'afgerond', 'in_uitvoering'], true);
                            $magVak6 = in_array($status, ['goedgekeurd', 'in_uitvoering'], true);
                            $magVak7 = false;

                            if ($magVak6 && databaseTableExists($pdo, 'werkvergunning_vak6_log')) {
                                $checkStmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
                                $checkStmt->execute(['id' => (int) $aanvraag['id']]);
                                $row = $checkStmt->fetch();
                                if (is_array($row)) {
                                    $magVak7 = alleVak6DagenVolledig($pdo, (int) $row['id'], $row) && $status !== 'afgerond';
                                }
                            }
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

                                        <?php if ($magVak6): ?>
                                            <button
                                                class="small-btn"
                                                type="button"
                                                onclick="window.location.href='aanvraag_vak6.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Vak VI
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($magVak7): ?>
                                            <button
                                                class="small-btn"
                                                type="button"
                                                onclick="window.location.href='aanvraag_vak7.php?id=<?= e((string) $aanvraag['id']) ?>'"
                                            >
                                                Vak VII
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($magActieAanvragen): ?>
                                            <form action="aanvraag_actie_aanvragen.php" method="POST" data-confirm-title="Aanpassing aanvragen" data-confirm-message="Wilt u vragen om deze aanvraag opnieuw te mogen aanpassen?" data-confirm-solution="Een leerkracht, TA of admin krijgt hiervan een melding.">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <input type="hidden" name="actie" value="aanpassen">
                                                <button type="submit" class="small-btn">
                                                    Aanpassen aanvragen
                                                </button>
                                            </form>

                                            <form action="aanvraag_actie_aanvragen.php" method="POST" data-confirm-title="Verwijdering aanvragen" data-confirm-message="Wilt u vragen om deze aanvraag te verwijderen?" data-confirm-solution="Een leerkracht, TA of admin krijgt hiervan een melding.">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <input type="hidden" name="actie" value="verwijderen">
                                                <button type="submit" class="small-btn delete-btn">
                                                    Verwijderen aanvragen
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
