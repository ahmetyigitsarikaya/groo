<?php
declare(strict_types=1);

final class Install_006_crm
{
    public function up(PDO $pdo): void
    {
        $repo = new \App\Core\Database\DynamicEntityRepository($pdo);
        if ($repo->getEntity('crm_contact') === null) {
            $repo->createEntity('crm_contact', 'CRM Contact', 'CRM Müşteri/Cari Kaydı');
            $repo->addAttribute('crm_contact', 'name', 'string', true);
            $repo->addAttribute('crm_contact', 'type', 'string', false);
            $repo->addAttribute('crm_contact', 'email', 'string', false);
            $repo->addAttribute('crm_contact', 'phone', 'string', false);
            $repo->addAttribute('crm_contact', 'address', 'string', false);
            $repo->addAttribute('crm_contact', 'status', 'string', false);
            $repo->addAttribute('crm_contact', 'tags', 'array', false);
            $repo->addAttribute('crm_contact', 'custom_fields', 'json', false);
        }

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_task_types (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                color VARCHAR(20) NOT NULL DEFAULT "#2563eb",
                icon VARCHAR(50) NOT NULL DEFAULT "📋",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_tasks (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                contact_id INT UNSIGNED NULL,
                assigned_to INT UNSIGNED NULL,
                task_type_id INT UNSIGNED NULL,
                title VARCHAR(191) NOT NULL,
                description TEXT NULL,
                fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(50) NOT NULL DEFAULT "pending",
                priority VARCHAR(50) NOT NULL DEFAULT "medium",
                due_date DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_contact (contact_id),
                KEY idx_assigned (assigned_to),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_invoices (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                invoice_number VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL DEFAULT "income",
                category VARCHAR(191) NULL,
                contact_id INT UNSIGNED NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(50) NOT NULL DEFAULT "unpaid",
                due_date DATE NULL,
                items JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_notifications (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NULL,
                title VARCHAR(191) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                type VARCHAR(50) NOT NULL DEFAULT "general",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_notes (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                contact_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NULL,
                note TEXT NOT NULL,
                activity_type VARCHAR(50) NOT NULL DEFAULT "note",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crm_expenses (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                contact_id INT UNSIGNED NULL,
                title VARCHAR(191) NOT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                type ENUM("income", "expense") NOT NULL DEFAULT "expense",
                category VARCHAR(50) NULL,
                expense_date DATE NOT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_contact (contact_id),
                KEY idx_type (type),
                KEY idx_date (expense_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS crm_notes');
        $pdo->exec('DROP TABLE IF EXISTS crm_notifications');
        $pdo->exec('DROP TABLE IF EXISTS crm_invoices');
        $pdo->exec('DROP TABLE IF EXISTS crm_expenses');
        $pdo->exec('DROP TABLE IF EXISTS crm_tasks');
        $pdo->exec('DROP TABLE IF EXISTS crm_task_types');
        $pdo->exec('DROP TABLE IF EXISTS crm_contacts');

        try {
            $repo = new \App\Core\Database\DynamicEntityRepository($pdo);
            $entity = $repo->getEntity('crm_contact');
            if ($entity) {
                $pdo->exec("DELETE FROM entity_records WHERE entity_id = " . (int)$entity['id']);
                $pdo->exec("DELETE FROM attributes WHERE entity_id = " . (int)$entity['id']);
                $pdo->exec("DELETE FROM entities WHERE id = " . (int)$entity['id']);
            }
        } catch (\Throwable $e) {}
    }
}
