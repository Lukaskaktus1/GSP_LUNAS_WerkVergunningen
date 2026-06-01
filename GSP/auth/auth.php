<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    setFlashMessage('error', 'Log eerst in om verder te gaan.');
    redirect('../index.php');
}

if (!defined('GSP_USER_MENU_INJECTED')) {
    define('GSP_USER_MENU_INJECTED', true);
    ob_start(static function (string $html): string {
        if (stripos($html, '</body>') === false || stripos($html, 'user-menu.js') !== false) {
            return $html;
        }

        $script = '<script src="/GSP/JS/user-menu.js"></script>';

        return str_ireplace('</body>', $script . "\n</body>", $html);
    });
}

function redirectToRoleOverview(): never
{
    $redirects = [
        'leerling' => 'overzicht_leerling.php',
        'leerkracht' => 'overzicht_leerkracht.php',
        'ta' => 'overzicht_ta.php',
        'directeur' => 'overzicht_directeur.php',
        'admin' => 'overzicht_admin.php',
    ];

    $role = (string) ($_SESSION['rol'] ?? '');
    redirect($redirects[$role] ?? 'overzicht_leerling.php');
}

function requireRole(array $allowedRoles): void
{
    $currentRole = (string) ($_SESSION['rol'] ?? '');
    if (!in_array($currentRole, $allowedRoles, true)) {
        redirectToRoleOverview();
    }
}
