<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['admin']);

$pdo = getDbConnection();

$stmt = $pdo->query("
    SELECT id, email, rol, actief, created_at
    FROM users
    ORDER BY created_at DESC
");

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
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <?= e(getCurrentUserRoleLabel()) ?>
                </span>
            </p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='overzicht_admin.php'">
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

        <?php if ($flash !== null): ?>
            <p style="margin-bottom:16px; color:<?= ($flash['type'] ?? '') === 'success' ? '#067647' : '#b42318' ?>;">
                <?= e((string) ($flash['message'] ?? '')) ?>
            </p>
        <?php endif; ?>

        <div class="applications-container">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="empty-state-text">Er zijn nog geen gebruikers.</div>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left; padding:12px;">ID</th>
                                <th style="text-align:left; padding:12px;">E-mail</th>
                                <th style="text-align:left; padding:12px;">Rol</th>
                                <th style="text-align:left; padding:12px;">Actief</th>
                                <th style="text-align:left; padding:12px;">Aangemaakt</th>
                                <th style="text-align:left; padding:12px;">Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr style="border-top:1px solid #ddd;">
                                <td style="padding:12px;"><?= e((string) $user['id']) ?></td>
                                <td style="padding:12px;"><?= e((string) $user['email']) ?></td>

                                <td style="padding:12px;">
                                    <form action="admin_gebruiker_update.php" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">

                                        <select name="rol" required>
                                            <?php foreach ($allowedRoles as $roleValue => $roleLabel): ?>
                                                <option value="<?= e($roleValue) ?>" <?= $user['rol'] === $roleValue ? 'selected' : '' ?>>
                                                    <?= e($roleLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>

                                <td style="padding:12px;">
                                        <select name="actief" required>
                                            <option value="1" <?= (int) $user['actief'] === 1 ? 'selected' : '' ?>>Actief</option>
                                            <option value="0" <?= (int) $user['actief'] === 0 ? 'selected' : '' ?>>Inactief</option>
                                        </select>
                                </td>

                                <td style="padding:12px;"><?= e((string) $user['created_at']) ?></td>

                                <td style="padding:12px;">
                                        <button type="submit" class="logout-btn">
                                            Opslaan
                                        </button>
                                    </form>
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