<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    setFlashMessage('error', 'Log eerst in om verder te gaan.');
    redirect('../index.php');
}

if (!defined('GSP_USER_MENU_LOADER')) {
    define('GSP_USER_MENU_LOADER', true);

    ob_start(static function (string $html): string {
        if (stripos($html, 'GSP/JS/user-menu.js') !== false || stripos($html, 'JS/user-menu.js') !== false) {
            return $html;
        }

        $css = '<link rel="stylesheet" href="/GSP/CSS/user-menu.css">';
        $script = '<script src="/GSP/JS/user-menu.js"></script>';

        if (stripos($html, '</head>') !== false && stripos($html, 'GSP/CSS/user-menu.css') === false) {
            $html = preg_replace('/<\/head>/i', "    {$css}\n</head>", $html, 1) ?? $html;
        }

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', "    {$script}\n</body>", $html, 1) ?? $html;
        }

        return $html . "\n" . $script;
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
