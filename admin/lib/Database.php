<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    public static function make(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . ($config['database'] ?? ':memory:');
                $pdo = new PDO($dsn);
            } elseif ($driver === 'mysql') {
                $host = $config['host'] ?? '127.0.0.1';
                $port = $config['port'] ?? 3306;
                $db = $config['database'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $host,
                    $port,
                    $db,
                    $charset
                );

                $pdo = new PDO(
                    $dsn,
                    (string)($config['username'] ?? ''),
                    (string)($config['password'] ?? '')
                );

                if (!empty($config['collation'])) {
                    $pdo->exec('SET NAMES ' . $charset . ' COLLATE ' . $config['collation']);
                }
            } else {
                throw new RuntimeException('Unsupported database driver: ' . $driver);
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    }
}
