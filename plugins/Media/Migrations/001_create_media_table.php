<?php

declare(strict_types=1);

namespace Plugins\Media\Migrations;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `media_library` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `filename` varchar(255) NOT NULL,
                `url` varchar(255) NOT NULL,
                `mime_type` varchar(100) NOT NULL,
                `width` int(10) unsigned DEFAULT NULL,
                `height` int(10) unsigned DEFAULT NULL,
                `size_bytes` int(10) unsigned NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("DROP TABLE IF EXISTS `media_library`");
    }
};
