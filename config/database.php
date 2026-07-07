<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$databaseDriver = $_ENV['DB_DRIVER'] ?? 'sqlite';
$defaultConnection = $_ENV['DB_CONNECTION'] ?? $databaseDriver;
$sqliteDatabase = $_ENV['DB_DATABASE'] ?? ($basePath . '/database/database.sqlite');
$networkPort = (int) ($_ENV['DB_PORT'] ?? ($databaseDriver === 'pgsql' ? 5432 : 3306));

return [
    'default' => $defaultConnection,
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $networkPort,
            'database' => $_ENV['DB_DATABASE'] ?? 'infbyte',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $networkPort,
            'database' => $_ENV['DB_DATABASE'] ?? 'infbyte',
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $sqliteDatabase,
        ],
    ],
];
