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
    $name = trim((string) ($_SESSION['voornaam'] ?? '') . ' ' . (string) ($_SESSION['naam'] ?? ''));

    if ($name !== '') {
        return $name;
    }

    return (string) ($_SESSION['email'] ?? 'Gebruiker');
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
