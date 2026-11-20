<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Config;
use PDO;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function getInstance(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                (string) Config::get('db.driver', 'mysql'),
                (string) Config::get('db.host', '127.0.0.1'),
                (string) Config::get('db.port', '3306'),
                (string) Config::get('db.database', 'groo'),
                (string) Config::get('db.charset', 'utf8mb4')
            );

            self::$pdo = new PDO(
                $dsn,
                (string) Config::get('db.username', 'root'),
                (string) Config::get('db.password', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        }

        return self::$pdo;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
    }
}
