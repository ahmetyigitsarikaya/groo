<?php
declare(strict_types=1);

namespace Plugins\Install\Migrations;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();

        // 1. Add remember_token to um_users if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM `um_users` LIKE 'remember_token'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `um_users` ADD COLUMN `remember_token` VARCHAR(100) NULL DEFAULT NULL AFTER `status`");
        }

        // 2. Create login attempts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `um_login_attempts` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `email` varchar(191) NOT NULL,
                `attempts` int(10) unsigned NOT NULL DEFAULT 1,
                `locked_until` timestamp NULL DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ip_email` (`ip_address`, `email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("DROP TABLE IF EXISTS `um_login_attempts`");
        
        $stmt = $pdo->query("SHOW COLUMNS FROM `um_users` LIKE 'remember_token'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("ALTER TABLE `um_users` DROP COLUMN `remember_token`");
        }
    }
};
