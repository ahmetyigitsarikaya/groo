<?php

declare(strict_types=1);

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec('CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(32) NOT NULL DEFAULT "admin",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM admin_users');
        $statement->execute();
        $total = (int) ($statement->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);

        if ($total === 0) {
            $insert = $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $insert->execute([
                'Groo Admin',
                'admin@groo.test',
                password_hash('GrooAdmin!2026', PASSWORD_DEFAULT),
                'admin',
            ]);
        }
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec('DROP TABLE IF EXISTS admin_users');
    }
}
;