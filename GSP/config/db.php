<?php

declare(strict_types=1);

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = 'ID480922_DATABASE.db.webhosting.be';
    $database = 'ID480922_DATABASE';
    $username = 'ID480922_DATABASE';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'VUL_HIER_DB_WACHTWOORD_IN';

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database),
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (Throwable $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('De databaseverbinding kon niet worden gemaakt.');
    }

    return $pdo;
}
