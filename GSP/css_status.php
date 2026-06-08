<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

$cssDir = __DIR__ . '/CSS';
$files = [
    'overzicht.css',
    'overzicht_admin.css',
    'overzicht_directeur.css',
    'overzicht_leerkracht.css',
    'overzicht_leerling.css',
    'overzicht_ta.css',
    'admin_gebruikers.css',
    'local-icons.css',
];

echo "CSS status\n";
echo "Map: {$cssDir}\n\n";

foreach ($files as $file) {
    $path = $cssDir . DIRECTORY_SEPARATOR . $file;
    $exists = is_file($path);
    $size = $exists ? filesize($path) : 0;

    echo $file . ': ' . ($exists ? 'bestaat' : 'ontbreekt') . ', ' . (string) $size . " bytes\n";
}
