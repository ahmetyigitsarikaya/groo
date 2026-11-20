<?php

declare(strict_types=1);

namespace Plugins\DualContact\Migrations;

use App\Core\Database\Connection;

final class CreateDualContactMessagesTable_001
{
    public function up(): void
    {
        $pdo = Connection::getInstance();
        $sql = "CREATE TABLE IF NOT EXISTS dual_contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("DROP TABLE IF EXISTS dual_contact_messages");
    }
}
