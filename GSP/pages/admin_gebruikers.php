<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['leerkracht', 'ta', 'admin']);

$pdo = getDbConnection();
$role = (string) ($_SESSION['rol'] ?? '');
$userId = (int) ($_SESSION['user_id'] ?? 0);

$params = [];
$where = '1 = 1';
$hasProfile = databaseTableExists($pdo, 'user_profiel');
$hasProfileKlas = $hasProfile && databaseColumnExists($pdo, 'user_profiel', 'klas');
$hasUserKlasVak = databaseTableExists($pdo, 'user_klas_vak');

if ($role === 'leerkracht') {
    $klassen = array_values(array_unique(array_filter(array_map(
        static fn (array $row): string => normalizeKlasNaam((string) ($row['klas'] ?? '')),
        userKlasVakProfielen($pdo, $userId)
    ))));

    if ($klassen === []) {
        $where = '1 = 0';
    } else {
        $placeholders = [];
        foreach ($klassen as $index => $klas) {
            $key = 'klas_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $klas;
        }
        $classExpression = $hasUserKlasVak && $hasProfileKlas
            ? "LOWER(COALESCE(ukv.klas, p.klas, ''))"
            : ($hasUserKlasVak ? "LOWER(COALESCE(ukv.klas, ''))" : ($hasProfileKlas ? "LOWER(COALESCE(p.klas, ''))" : "''"));
        $where = "u.rol = 'leerling' AND {$classExpression} IN (" . implode(', ', $placeholders) . ')';
    }
}

$profileJoin = $hasProfile ? 'LEFT JOIN user_profiel p ON p.user_id = u.id' : '';
$classJoin = $hasUserKlasVak ? 'LEFT JOIN user_klas_vak ukv ON ukv.user_id = u.id' : '';
$klasSelect = $hasProfileKlas
    ? 'p.klas AS profiel_klas'
    : "NULL AS profiel_klas";
$gekoppeldeSelect = $hasUserKlasVak ? 'ukv.klas AS gekoppelde_klas' : "NULL AS gekoppelde_klas";

$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.email, u.rol, u.actief, u.created_at, {$klasSelect}, {$gekoppeldeSelect}
    FROM users u
    {$profileJoin}
    {$classJoin}
    WHERE {$where}
    ORDER BY u.created_at DESC
");

$stmt->execute($params);

$users = $stmt->fetchAll();
$flash = getFlashMessage();

$allowedRoles = [
    'leerling' => 'Leerling/Externe',
    'leerkracht' => 'Leerkracht',
    'ta' => 'TA',
    'directeur' => 'Directeur',
    'admin' => 'Admin',
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebruikersbeheer - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/admin_gebruikers.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
    <?= gspInlineCss(['overzicht.css', 'admin_gebruikers.css', 'local-icons.css']) ?>
    <?= gspOverviewCriticalCss() ?>
</head>

<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon">
            <i class="far fa-file-lines"></i>
        </div>
        <div class="header-title">
            <h1>Gebruikersbeheer</h1>
            <p>
                Welkom,
                <span class="role-badge">
                    <i class="fas fa-user-shield"></i>
                    <?= e(currentUserDisplayName()) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='<?= e(match ($role) {
            'leerkracht' => 'overzicht_leerkracht.php',
            'ta' => 'overzicht_ta.php',
            default => 'overzicht_admin.php',
        }) ?>'">
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
        <h2 class="section-title">Gebruikers</h2>

        <?= flashDialogMarkup($flash) ?>

        <div class="applications-container">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="empty-state-text">Er zijn nog geen gebruikers.</div>
                </div>
            <?php else: ?>
                <div class="user-table-wrap">
                    <table class="user-management-table">
                        <thead>
                            <tr>
                                <th style="text-align:left; padding:12px;">ID</th>
                                <th style="text-align:left; padding:12px;">E-mail</th>
                                <th style="text-align:left; padding:12px;">Rol</th>
                                <th style="text-align:left; padding:12px;">Klas</th>
                                <th style="text-align:left; padding:12px;">Actief</th>
                                <th style="text-align:left; padding:12px;">Aangemaakt</th>
                                <th style="text-align:left; padding:12px;">Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $rowId = (int) $user['id'];
                            $updateFormId = 'user_update_' . $rowId;
                            $deleteFormId = 'user_delete_' . $rowId;
                            $isCurrentUser = $rowId === (int) ($_SESSION['user_id'] ?? 0);
                            ?>
                            <tr>
                                <td><?= e((string) $user['id']) ?></td>
                                <td><?= e((string) $user['email']) ?></td>

                                <td>
                                    <form id="<?= e($updateFormId) ?>" action="admin_gebruiker_update.php" method="POST"></form>
                                    <input form="<?= e($updateFormId) ?>" type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">

                                        <select form="<?= e($updateFormId) ?>" name="rol" required <?= $role === 'leerkracht' ? 'disabled' : '' ?>>
                                            <?php foreach ($allowedRoles as $roleValue => $roleLabel): ?>
                                                <option value="<?= e($roleValue) ?>" <?= $user['rol'] === $roleValue ? 'selected' : '' ?>>
                                                    <?= e($roleLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($role === 'leerkracht'): ?>
                                            <input form="<?= e($updateFormId) ?>" type="hidden" name="rol" value="<?= e((string) $user['rol']) ?>">
                                        <?php endif; ?>
                                </td>

                                <td>
                                    <?= e(trim((string) ($user['gekoppelde_klas'] ?? $user['profiel_klas'] ?? '')) ?: 'Niet gekoppeld') ?>
                                </td>

                                <td>
                                        <select form="<?= e($updateFormId) ?>" name="actief" required>
                                            <option value="1" <?= (int) $user['actief'] === 1 ? 'selected' : '' ?>>Actief</option>
                                            <option value="0" <?= (int) $user['actief'] === 0 ? 'selected' : '' ?>>Inactief</option>
                                        </select>
                                </td>

                                <td><?= e((string) $user['created_at']) ?></td>

                                <td>
                                    <div class="user-action-stack">
                                        <button form="<?= e($updateFormId) ?>" type="submit" class="user-action-btn save">
                                            Opslaan
                                        </button>
                                    <form id="<?= e($deleteFormId) ?>" action="admin_gebruiker_update.php" method="POST" data-confirm-title="Account verwijderen" data-confirm-message="Weet u zeker dat u dit account wilt verwijderen?" data-confirm-solution="Als het account nog geen aanvragen heeft, wordt het volledig verwijderd. Anders wordt het veilig gedeactiveerd zodat bestaande aanvragen bewaard blijven.">
                                        <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                        <input type="hidden" name="rol" value="<?= e((string) $user['rol']) ?>">
                                        <input type="hidden" name="actief" value="0">
                                        <input type="hidden" name="actie" value="verwijderen">
                                        <button type="submit" class="user-action-btn delete" <?= $isCurrentUser ? 'disabled' : '' ?>>
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
</body>
</html>
