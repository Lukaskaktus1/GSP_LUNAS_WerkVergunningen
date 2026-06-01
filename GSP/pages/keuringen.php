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

                                            <form action="aanvraag_keuren.php" method="POST" data-confirm-title="Aanvraag goedkeuren" data-confirm-message="Weet u zeker dat u deze aanvraag wilt goedkeuren?" data-confirm-solution="Controleer eerst of de risico's en maatregelen volledig genoeg zijn. Na bevestigen wordt de aanvraag goedgekeurd.">
                                                <input type="hidden" name="id" value="<?= e((string) $aanvraag['id']) ?>">
                                                <input type="hidden" name="actie" value="goedkeuren">
                                                <button type="submit" class="small-btn approve-btn">
                                                    Goedkeuren
                                                </button>
                                            </form>

                                            <form action="aanvraag_keuren.php" method="POST" data-confirm-title="Aanvraag afkeuren" data-confirm-message="Weet u zeker dat u deze aanvraag wilt afkeuren?" data-confirm-solution="Gebruik dit alleen wanneer de aanvraag niet veilig of niet volledig genoeg is. De aanvrager ziet daarna dat de aanvraag afgekeurd is.">
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
<script src="../JS/ui-feedback.js"></script>
</body>
</html>
