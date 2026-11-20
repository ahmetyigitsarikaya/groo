<?php
declare(strict_types=1);

final class Install_004_traffic_analytics
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS traffic_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                url VARCHAR(255) NOT NULL,
                user_agent VARCHAR(255) NULL,
                referer VARCHAR(255) NULL,
                is_bot TINYINT(1) NOT NULL DEFAULT 0,
                bot_name VARCHAR(255) NULL,
                device_type VARCHAR(50) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_created_at (created_at),
                KEY idx_url (url)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS page_metrics (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                url VARCHAR(191) NOT NULL,
                total_views INT UNSIGNED NOT NULL DEFAULT 0,
                unique_views INT UNSIGNED NOT NULL DEFAULT 0,
                bot_views INT UNSIGNED NOT NULL DEFAULT 0,
                success_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                last_visited_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_url (url)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS page_metrics');
        $pdo->exec('DROP TABLE IF EXISTS traffic_logs');
    }
}
