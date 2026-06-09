<?php
// Optional endpoint for saving uploaded samples when running with a PHP server.
// The main app also supports browser-only uploads, so this file is not required
// for the sequencer to work locally.

header('Content-Type: application/json');

$targetDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR;
$maxUploadBytes = 60 * 1024 * 1024;
$allowedTypes = [
    'audio/wav',
    'audio/x-wav',
    'audio/mpeg',
    'audio/mp3',
    'audio/ogg',
    'audio/flac',
    'audio/aiff',
    'audio/x-aiff'
];
$allowedExtensions = [
    'wav',
    'mp3',
    'ogg',
    'flac',
    'aif',
    'aiff'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST uploads are allowed.']);
    exit;
}

if (!isset($_FILES['sample']) || $_FILES['sample']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid sample was uploaded.']);
    exit;
}

$file = $_FILES['sample'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['size'] > $maxUploadBytes) {
    http_response_code(413);
    echo json_encode(['error' => 'The sample is too large. Maximum size is 60 MB.']);
    exit;
}

if (!in_array($file['type'], $allowedTypes, true) && !in_array($extension, $allowedExtensions, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only common audio files are allowed.']);
    exit;
}

if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0775, true);
}

$baseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
$safeName = trim($baseName, '-') ?: 'sample';
$targetName = $safeName . '-' . date('Ymd-His') . '.' . $extension;
$targetPath = $targetDirectory . $targetName;
$type = isset($_POST['type']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['type']) : 'uploaded';
$pack = isset($_POST['pack']) ? trim(strip_tags($_POST['pack'])) : 'Uploads';

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'The sample could not be saved.']);
    exit;
}

echo json_encode([
    'id' => 'server-' . pathinfo($targetName, PATHINFO_FILENAME),
    'name' => pathinfo($targetName, PATHINFO_FILENAME),
    'path' => 'samples/' . $targetName,
    'type' => $type ?: 'uploaded',
    'pack' => $pack ?: 'Uploads',
    'source' => 'server',
    'size' => filesize($targetPath)
]);
