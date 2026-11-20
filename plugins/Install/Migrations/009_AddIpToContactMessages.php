<?php
declare(strict_types=1);

namespace Plugins\Install\Migrations;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        try {
            $pdo->exec("ALTER TABLE dual_contact_messages ADD COLUMN ip_address VARCHAR(45) NULL AFTER message");
        } catch (\PDOException $e) {
            // Sütun zaten varsa hata verebilir, yoksay.
        }
    }
};
