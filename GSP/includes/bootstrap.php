<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function gspSiteRootRelativePrefix(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (preg_match('#/GSP/(pages|PHP)/#i', $scriptName) === 1) {
        return '../..';
    }

    if (preg_match('#/GSP/#i', $scriptName) === 1 || substr($scriptName, -4) === '/GSP') {
        return '..';
    }

    return '.';
}

function gspLegalComplianceHead(): string
{
    $prefix = gspSiteRootRelativePrefix();

    return '<link rel="stylesheet" href="' . e($prefix . '/CSS/legal-compliance.css') . '">' . "\n"
        . '<script src="' . e($prefix . '/Scripts/legal-compliance.js') . '" defer></script>';
}

function gspLegalComplianceBody(): string
{
    $prefix = gspSiteRootRelativePrefix();
    $cookieUrl = e($prefix . '/cookiebeleid.html');
    $privacyUrl = e($prefix . '/privacybeleid.html');
    $termsUrl = e($prefix . '/algemene-voorwaarden.html');

    return <<<HTML
<footer class="legal-footer" data-site-legal-footer>
    <div class="legal-footer__inner">
        <p>&copy; GTI Beveren 2025-2026 - GSP 6ADB</p>
        <nav aria-label="Juridische links">
            <a href="{$cookieUrl}">Cookiebeleid</a>
            <a href="{$privacyUrl}">Privacybeleid</a>
            <a href="{$termsUrl}">Algemene voorwaarden</a>
        </nav>
    </div>
</footer>
<section class="cookie-banner" id="siteCookieBanner" data-site-cookie-banner aria-label="Cookie melding">
    <div>
        <strong>Cookies op deze website</strong>
        <p>We gebruiken noodzakelijke cookies voor de werking van de site. Analytics gebruiken we alleen na jouw toestemming. Lees meer in ons <a href="{$cookieUrl}">cookiebeleid</a> en <a href="{$privacyUrl}">privacybeleid</a>.</p>
    </div>
    <div class="cookie-actions">
        <button type="button" data-cookie-decline>Weigeren</button>
        <button class="primary-action" type="button" data-cookie-accept>Accepteren</button>
    </div>
</section>
HTML;
}

function gspInjectLegalCompliance(string $html): string
{
    if (
        stripos($html, '<html') === false
        || stripos($html, '</head>') === false
        || stripos($html, '</body>') === false
    ) {
        return $html;
    }

    if (stripos($html, 'legal-compliance.css') === false) {
        $html = preg_replace('/<\/head>/i', gspLegalComplianceHead() . "\n</head>", $html, 1) ?? $html;
    }

    if (stripos($html, 'data-site-legal-footer') === false) {
        $html = preg_replace('/<\/body>/i', gspLegalComplianceBody() . "\n</body>", $html, 1) ?? $html;
    }

    return $html;
}

ob_start('gspInjectLegalCompliance');

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function gspInlineCss(array $fileNames): string
{
    $cssDir = realpath(__DIR__ . '/../CSS');

    if ($cssDir === false) {
        return '';
    }

    $blocks = [];

    foreach ($fileNames as $fileName) {
        $safeName = basename((string) $fileName);
        $cssPath = realpath($cssDir . DIRECTORY_SEPARATOR . $safeName);

        if ($cssPath === false || strpos($cssPath, $cssDir) !== 0 || !is_file($cssPath)) {
            continue;
        }

        $css = file_get_contents($cssPath);

        if ($css === false || trim($css) === '') {
            continue;
        }

        $css = str_replace('</style', '<\/style', $css);
        $blocks[] = '<style data-required-css="' . e($safeName) . '">' . $css . '</style>';
    }

    return implode("\n", $blocks);
}

function gspOverviewCriticalCss(): string
{
    return <<<'HTML'
<style data-critical-css="overview-pages">
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:#eef6ff;color:#333;font-family:Arial,sans-serif;line-height:1.6}.header{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;padding:16px 40px;background:rgba(255,255,255,.88);box-shadow:0 18px 45px rgba(15,23,42,.12)}.header-left{display:flex;align-items:center;gap:15px}.header-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#d4e4fa;color:#1678fa}.header-title h1{margin:0;font-size:20px}.header-title p{margin:4px 0 0;color:#666;font-size:14px}.header-center{flex:1;display:flex;justify-content:center}.header-logo{max-height:86px;max-width:min(520px,100%);object-fit:contain}.header-right{display:flex;align-items:center;gap:10px}.role-badge{display:inline-flex;align-items:center;gap:6px;margin-left:8px;padding:6px 12px;border-radius:999px;background:#fff;color:#333}.logout-btn,.empty-state-button,.small-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(148,163,184,.25);border-radius:8px;background:rgba(255,255,255,.88);color:#333;cursor:pointer;font-weight:700;padding:8px 12px;text-decoration:none;box-shadow:0 10px 24px rgba(15,23,42,.10)}.main-container{max-width:1200px;margin:36px auto;padding:0 40px}.section-title{margin:0 0 18px;font-size:24px}.quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px}.action-card{min-height:170px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:8px;padding:26px;border:1px solid rgba(255,255,255,.72);border-radius:18px;background:rgba(255,255,255,.84);box-shadow:0 24px 70px rgba(15,23,42,.12);cursor:pointer;transition:transform .2s ease,box-shadow .2s ease}.action-card:hover{transform:translateY(-3px);box-shadow:0 26px 76px rgba(15,23,42,.18)}.action-card.highlighted{border:2px solid rgba(37,99,235,.45);background:linear-gradient(145deg,rgba(224,242,254,.92),rgba(236,253,245,.88))}.action-card-icon,.empty-state-icon{width:64px;height:64px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f3f4f6;color:#6b7280;font-size:28px}.action-card.highlighted .action-card-icon{background:linear-gradient(135deg,#2563eb,#0891b2);color:#fff}.action-card-title{font-size:18px;font-weight:800}.action-card-subtitle{font-size:14px;color:#666}.applications-section{margin-top:40px}.applications-container{min-height:300px;padding:32px;border-radius:18px;background:rgba(255,255,255,.84);box-shadow:0 24px 70px rgba(15,23,42,.12)}.empty-state{min-height:240px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:18px;color:#6b7280}.aanvragen-table,.keuringen-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}.aanvragen-table th,.aanvragen-table td,.keuringen-table th,.keuringen-table td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:middle}.keuringen-table tbody tr:hover{background:rgba(37,99,235,.06)}.table-actions{display:flex;gap:8px;flex-wrap:wrap}.delete-btn,.reject-btn{color:#991b1b;background:#fee2e2}.open-btn{background:#e0f2fe;color:#075985}.approve-btn{background:#dcfce7;color:#166534}.status-badge{display:inline-flex;padding:5px 10px;border-radius:999px;font-weight:800}.status-goedgekeurd{background:#dcfce7;color:#166534}.status-afgekeurd{background:#fee2e2;color:#991b1b}.status-wachtend{background:#fef3c7;color:#92400e}.status-concept,.status-onbekend{background:#e5e7eb;color:#374151}.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.detail-field{display:flex;flex-direction:column;gap:6px}.detail-field.full{grid-column:1/-1}.detail-field label{font-weight:700;color:#374151}.readonly-box{min-height:44px;padding:12px 14px;border:1px solid #d1d5db;border-radius:10px;background:rgba(249,250,251,.88);color:#111827;white-space:pre-wrap}.aanvraag-photo{display:block;width:min(100%,520px);max-height:360px;object-fit:contain;border:1px solid #d1d5db;border-radius:10px;background:rgba(249,250,251,.88)}@media(max-width:760px){.header{padding:14px}.header-center{order:3;width:100%}.header-right{width:100%;justify-content:flex-start}.main-container{padding:0 14px;margin:24px auto}.quick-actions,.detail-grid{grid-template-columns:1fr}.applications-container{padding:18px}}
</style>
HTML;
}
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlashMessage(): ?array
{
    if (!isset($_SESSION['flash_message']) || !is_array($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $flash;
}

function flashDialogMarkup(?array $flash): string
{
    if ($flash === null) {
        return '';
    }

    $type = (string) ($flash['type'] ?? 'info');
    $message = (string) ($flash['message'] ?? '');

    if ($message === '') {
        return '';
    }

    $title = match ($type) {
        'success' => 'Gelukt',
        'error' => 'Er is iets misgelopen',
        default => 'Melding',
    };

    $solution = match ($type) {
        'success' => 'U hoeft niets extra te doen. U kunt verder werken.',
        'error' => 'Controleer de velden en probeer opnieuw. Blijft dit gebeuren, vraag hulp aan de beheerder.',
        default => 'Lees de melding en ga daarna verder.',
    };

    return '<div hidden data-app-flash data-type="' . e($type) . '" data-title="' . e($title) . '" data-message="' . e($message) . '" data-solution="' . e($solution) . '"></div>';
}

function latestReviewNotification(PDO $pdo): ?array
{
    $role = (string) ($_SESSION['rol'] ?? '');
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $params = [];
    $where = "w.status IN ('ingediend', 'in_beoordeling')";

    if ($role === 'leerkracht') {
        if (!databaseColumnExists($pdo, 'werkvergunning', 'vak2_klas')) {
            return null;
        }

        $teacherKlassen = userKlasVakProfielen($pdo, $userId);
        $klassen = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => strtolower(trim((string) ($row['klas'] ?? ''))),
            $teacherKlassen
        ))));

        if ($klassen === []) {
            return null;
        }

        $placeholders = [];
        foreach ($klassen as $index => $klas) {
            $key = 'klas_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $klas;
        }

        $where .= " AND w.vak2_doel = 'school' AND LOWER(COALESCE(w.vak2_klas, '')) IN (" . implode(', ', $placeholders) . ')';
    } elseif (!in_array($role, ['directeur', 'ta', 'admin'], true)) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT w.id, w.vergunning_nummer, w.eigenaar_email, w.eigenaar_rol, w.werkbeschrijving, w.created_at
        FROM werkvergunning w
        WHERE {$where}
        ORDER BY w.created_at DESC
        LIMIT 1
    ");

    $stmt->execute($params);

    $aanvraag = $stmt->fetch();

    return is_array($aanvraag) ? $aanvraag : null;
}

function gspAfdelingen(): array
{
    return [
        'auto' => 'Auto',
        'mechanica' => 'Mechanica',
        'lassen' => 'Lassen',
        'hout' => 'Hout',
        'elektriciteit' => 'Elektriciteit',
        'eerste_graad' => 'Eerste graad',
    ];
}

function gspKlassen(): array
{
    $klassen = [];

    foreach (['1A', '1B', '1C', '1D', '2A', '2B', '2C', '2D'] as $klas) {
        $klassen[$klas] = $klas;
    }

    $bovenbouw = [
        'AUTO' => 'Auto',
        'MECH' => 'Mechanica',
        'LAS' => 'Lassen',
        'HOUT' => 'Hout',
        'ELEK' => 'Elektriciteit',
        'ADB' => 'Applicatie- en databeheer',
    ];

    foreach ([3, 4, 5, 6, 7] as $jaar) {
        foreach ($bovenbouw as $richtingCode => $richtingLabel) {
            if ($jaar === 7 && $richtingCode === 'ADB') {
                continue;
            }

            $code = (string) $jaar . $richtingCode;
            $klassen[$code] = $code . ' - ' . $richtingLabel;
        }
    }

    return $klassen;
}

function normalizeKlasNaam(string $klas): string
{
    return strtolower(trim($klas));
}

function userKlasVakProfielen(PDO $pdo, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $rows = [];

    if (
        databaseTableExists($pdo, 'user_klas_vak')
        && databaseColumnExists($pdo, 'user_klas_vak', 'user_id')
    ) {
        $stmt = $pdo->prepare('
            SELECT klas, vak
            FROM user_klas_vak
            WHERE user_id = :user_id
            ORDER BY id ASC
        ');
        $stmt->execute(['user_id' => $userId]);
        $rows = array_merge($rows, array_filter($stmt->fetchAll(), 'is_array'));
    }

    if (
        databaseTableExists($pdo, 'user_profiel')
        && databaseColumnExists($pdo, 'user_profiel', 'user_id')
    ) {
        $select = [];
        foreach (['klas', 'vak'] as $column) {
            if (databaseColumnExists($pdo, 'user_profiel', $column)) {
                $select[] = $column;
            }
        }

        if ($select !== []) {
            $stmt = $pdo->prepare('SELECT ' . implode(', ', $select) . ' FROM user_profiel WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $profile = $stmt->fetch();

            if (is_array($profile)) {
                $rows[] = [
                    'klas' => (string) ($profile['klas'] ?? ''),
                    'vak' => (string) ($profile['vak'] ?? ''),
                ];
            }
        }
    }

    $clean = [];
    foreach ($rows as $row) {
        $klas = trim((string) ($row['klas'] ?? ''));
        $vak = trim((string) ($row['vak'] ?? ''));

        if ($klas === '' && $vak === '') {
            continue;
        }

        $clean[] = ['klas' => $klas, 'vak' => $vak];
    }

    return $clean;
}

function leerkrachtMagKlasBeheren(PDO $pdo, int $teacherId, string $klas): bool
{
    $klas = normalizeKlasNaam($klas);

    if ($klas === '') {
        return false;
    }

    foreach (userKlasVakProfielen($pdo, $teacherId) as $row) {
        if (normalizeKlasNaam((string) ($row['klas'] ?? '')) === $klas) {
            return true;
        }
    }

    return false;
}

function reviewNotificationMarkup(?array $aanvraag): string
{
    if ($aanvraag === null) {
        return '';
    }

    $id = (string) ($aanvraag['id'] ?? '');

    if ($id === '') {
        return '';
    }

    $aanvrager = (string) ($aanvraag['eigenaar_email'] ?? 'Onbekende aanvrager');
    $nummer = (string) ($aanvraag['vergunning_nummer'] ?? 'Nieuwe aanvraag');
    $werk = trim((string) ($aanvraag['werkbeschrijving'] ?? ''));

    return '<div hidden data-review-notification data-id="' . e($id) . '" data-aanvrager="' . e($aanvrager) . '" data-nummer="' . e($nummer) . '" data-werk="' . e($werk) . '" data-url="aanvraag_bekijken.php?id=' . e($id) . '"></div>';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getCurrentUserRoleLabel(): string
{
    $role = $_SESSION['rol'] ?? '';

    return match ($role) {
        'leerling' => 'Leerling/Externe',
        'leerkracht' => 'Leerkracht',
        'ta' => 'TA',
        'directeur' => 'Directeur',
        'admin' => 'Admin',
        default => 'Gebruiker',
    };
}

function currentUserDisplayName(): string
{
    $voornaam = trim((string) ($_SESSION['voornaam'] ?? ''));
    $achternaam = trim((string) ($_SESSION['naam'] ?? $_SESSION['achternaam'] ?? ''));
    $name = trim($voornaam . ' ' . $achternaam);

    if ($name !== '') {
        return $name;
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));

    return $email !== '' ? $email : 'Gebruiker';
}

function passwordMeetsPolicy(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[0-9]/', $password) === 1
        && preg_match('/[^A-Za-z0-9]/', $password) === 1;
}

function passwordPolicyMessage(): string
{
    return 'Het wachtwoord moet minstens 8 tekens, 1 cijfer en 1 speciaal teken bevatten.';
}

function databaseColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name
    ");

    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function databaseTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
    ");

    $stmt->execute([
        'table_name' => $table,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function optionalTableColumns(PDO $pdo, string $table, array $wantedColumns): array
{
    $available = [];

    foreach ($wantedColumns as $column) {
        if (databaseColumnExists($pdo, $table, $column)) {
            $available[] = $column;
        }
    }

    return $available;
}

function sendPortalMail(string $to, string $subject, string $message): bool
{
    $mailHost = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromAddress = 'noreply@' . ($mailHost !== '' ? $mailHost : 'localhost');
    $headers = [
        'From: Werkvergunning Portaal <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($to, $subject, $message, implode("\r\n", $headers));
}

function appOrigin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return rtrim($scheme . '://' . $host, '/');
}

function appBaseUrl(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/GSP/index.php'));
    $gspPosition = strpos($scriptName, '/GSP/');
    $basePath = $gspPosition === false ? '/GSP' : substr($scriptName, 0, $gspPosition + 4);

    return rtrim(appOrigin() . $basePath, '/');
}

function gspRelativeAssetPrefix(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (preg_match('#/GSP/(pages|PHP)/#i', $scriptName) === 1) {
        return '..';
    }

    return '.';
}

function passwordResetUsesTokenTable(PDO $pdo): bool
{
    return databaseTableExists($pdo, 'wachtwoord_reset_token')
        && databaseColumnExists($pdo, 'wachtwoord_reset_token', 'token_hash')
        && databaseColumnExists($pdo, 'wachtwoord_reset_token', 'vervalt_op');
}

function createPasswordResetToken(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

    if (passwordResetUsesTokenTable($pdo)) {
        $stmt = $pdo->prepare('
            INSERT INTO wachtwoord_reset_token (user_id, token_hash, vervalt_op)
            VALUES (:user_id, :token_hash, :vervalt_op)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'vervalt_op' => $expiresAt,
        ]);

        return $token;
    }

    if (
        !databaseColumnExists($pdo, 'users', 'reset_token')
        || !databaseColumnExists($pdo, 'users', 'reset_expires_at')
    ) {
        throw new RuntimeException('Wachtwoordherstel is niet geconfigureerd in de database.');
    }

    $update = $pdo->prepare('
        UPDATE users
        SET reset_token = :token,
            reset_expires_at = :expires_at
        WHERE id = :id
    ');
    $update->execute([
        'token' => $token,
        'expires_at' => $expiresAt,
        'id' => $userId,
    ]);

    return $token;
}

function findUserIdByPasswordResetToken(PDO $pdo, string $token): ?int
{
    if ($token === '') {
        return null;
    }

    if (passwordResetUsesTokenTable($pdo)) {
        $stmt = $pdo->prepare('
            SELECT user_id
            FROM wachtwoord_reset_token
            WHERE token_hash = :token_hash
              AND vervalt_op >= NOW()
              AND gebruikt_op IS NULL
            LIMIT 1
        ');
        $stmt->execute(['token_hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();

        return is_array($row) ? (int) ($row['user_id'] ?? 0) : null;
    }

    if (
        !databaseColumnExists($pdo, 'users', 'reset_token')
        || !databaseColumnExists($pdo, 'users', 'reset_expires_at')
    ) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT id
        FROM users
        WHERE reset_token = :token
          AND reset_expires_at >= NOW()
          AND actief = 1
        LIMIT 1
    ');
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    return is_array($row) ? (int) ($row['id'] ?? 0) : null;
}

function completePasswordReset(PDO $pdo, int $userId, string $token, string $passwordHash): void
{
    $update = $pdo->prepare('
        UPDATE users
        SET wachtwoord_hash = :password_hash
        WHERE id = :id
    ');
    $update->execute([
        'password_hash' => $passwordHash,
        'id' => $userId,
    ]);

    if (passwordResetUsesTokenTable($pdo)) {
        $markUsed = $pdo->prepare('
            UPDATE wachtwoord_reset_token
            SET gebruikt_op = NOW()
            WHERE user_id = :user_id
              AND token_hash = :token_hash
              AND gebruikt_op IS NULL
        ');
        $markUsed->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
        ]);
    }

    if (databaseColumnExists($pdo, 'users', 'reset_token')) {
        $clearLegacy = $pdo->prepare('
            UPDATE users
            SET reset_token = NULL,
                reset_expires_at = NULL
            WHERE id = :id
        ');
        $clearLegacy->execute(['id' => $userId]);
    }
}
