<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Database
{
    public static function connect(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_NAME'] ?? null;
        $username = $_ENV['DB_USER'] ?? null;
        $password = $_ENV['DB_PASSWORD'] ?? '';

        if ($host === null || $database === null || $username === null) {
            throw new RuntimeException(
                'Die Datenbankkonfiguration ist unvollständig.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        return new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}