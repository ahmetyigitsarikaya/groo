<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Exceptions\PluginException;
use PDO;

final class MigrationManager
{
    public const TABLE = 'groo_migrations';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    public function ensureTable(): void
    {
        $this->pdo->exec(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                scope VARCHAR(64) NOT NULL,
                version INT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_scope_version (scope, version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            self::TABLE
        ));
    }

    public function run(string $scope, string $path): array
    {
        $this->ensureTable();

        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        $applied = $this->appliedVersions($scope);
        $ran = [];

        foreach ($files as $file) {
            $base = basename($file, '.php');
            if (preg_match('/^(\d+)_(.+)$/', $base, $matches) !== 1) {
                continue;
            }

            $version = (int) $matches[1];
            if (in_array($version, $applied, true)) {
                continue;
            }

            $migration = $this->createMigration($file);
            $migration->up($this->pdo);

            $statement = $this->pdo->prepare(sprintf('INSERT INTO %s (scope, version, name) VALUES (?, ?, ?)', self::TABLE));
            $statement->execute([$scope, $version, $base]);

            $ran[] = $base;
        }

        return $ran;
    }

    public function appliedVersions(string $scope): array
    {
        $statement = $this->pdo->prepare(sprintf('SELECT version FROM %s WHERE scope = ? ORDER BY version', self::TABLE));
        $statement->execute([$scope]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function createMigration(string $file): object
    {
        $before = get_declared_classes();
        require $file;
        $new = array_diff(get_declared_classes(), $before);

        if ($new === []) {
            throw new PluginException(sprintf('Migration file "%s" does not declare a class.', $file));
        }

        $class = end($new);
        $migration = new $class();

        if (!method_exists($migration, 'up')) {
            throw new PluginException(sprintf('Migration "%s" must define an up(PDO $pdo) method.', $class));
        }

        return $migration;
    }
}
