<?php
declare(strict_types=1);

final class Install_001_core_tables
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS entities (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                slug VARCHAR(191) NOT NULL,
                name VARCHAR(191) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS attributes (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity_id INT UNSIGNED NOT NULL,
                code VARCHAR(191) NOT NULL,
                type VARCHAR(32) NOT NULL,
                required TINYINT(1) NOT NULL DEFAULT 0,
                options JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_entity_code (entity_id, code),
                CONSTRAINT fk_attributes_entity FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS entity_records (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity_id INT UNSIGNED NOT NULL,
                data JSON NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_entity (entity_id),
                CONSTRAINT fk_records_entity FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS entity_records');
        $pdo->exec('DROP TABLE IF EXISTS attributes');
        $pdo->exec('DROP TABLE IF EXISTS entities');
    }
}
