<?php
declare(strict_types=1);

final class Install_005_seo_engine
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS seo_settings (
                `key` VARCHAR(100) NOT NULL,
                `value` TEXT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS seo_meta (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                url_pattern VARCHAR(191) NOT NULL,
                title VARCHAR(255) NULL,
                description TEXT NULL,
                keywords TEXT NULL,
                og_title VARCHAR(255) NULL,
                og_description TEXT NULL,
                og_image VARCHAR(255) NULL,
                noindex TINYINT(1) NOT NULL DEFAULT 0,
                nofollow TINYINT(1) NOT NULL DEFAULT 0,
                schema_type VARCHAR(100) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_url_pattern (url_pattern)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS seo_redirects (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                old_url VARCHAR(191) NOT NULL,
                new_url VARCHAR(255) NOT NULL,
                status_code INT NOT NULL DEFAULT 301,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_old_url (old_url)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS seo_redirects');
        $pdo->exec('DROP TABLE IF EXISTS seo_meta');
        $pdo->exec('DROP TABLE IF EXISTS seo_settings');
    }
}
