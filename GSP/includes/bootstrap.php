<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
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
    $stmt = $pdo->query("
        SELECT id, vergunning_nummer, eigenaar_email, eigenaar_rol, werkbeschrijving, created_at
        FROM werkvergunning
        WHERE status IN ('ingediend', 'in_beoordeling')
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $aanvraag = $stmt->fetch();

    return is_array($aanvraag) ? $aanvraag : null;
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
    $headers = [
        'From: Werkvergunning Portaal <noreply@adbvandenweyer2205.be>',
        'Reply-To: noreply@adbvandenweyer2205.be',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($to, $subject, $message, implode("\r\n", $headers));
}

function appBaseUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/GSP/index.php'));
    $gspPosition = strpos($scriptName, '/GSP/');
    $basePath = $gspPosition === false ? '/GSP' : substr($scriptName, 0, $gspPosition + 4);

    return rtrim($scheme . '://' . $host . $basePath, '/');
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
