<?php

declare(strict_types=1);

use App\Core\Database\QueryBuilder;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        // 1. Create crm_task_types table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS crm_task_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                color VARCHAR(20) NOT NULL DEFAULT '#2563eb',
                icon VARCHAR(50) NOT NULL DEFAULT '📋',
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Add task_type_id & fee to crm_tasks if not exists
        try {
            $pdo->exec("ALTER TABLE crm_tasks ADD COLUMN task_type_id INT NULL AFTER contact_id");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE crm_tasks ADD COLUMN fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER task_type_id");
        } catch (\Throwable $e) {}

        // 3. Add type & category to crm_invoices if not exists
        try {
            $pdo->exec("ALTER TABLE crm_invoices ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'income' AFTER invoice_number");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE crm_invoices ADD COLUMN category VARCHAR(100) NULL AFTER type");
        } catch (\Throwable $e) {}

        // Seed Task Types if empty
        $countTypes = QueryBuilder::table('crm_task_types', $pdo)->count();
        if ($countTypes === 0) {
            $types = [
                ['name' => 'Yazılım & Destek', 'color' => '#2563eb', 'icon' => '💻'],
                ['name' => 'Toplantı & Görüşme', 'color' => '#8b5cf6', 'icon' => '🤝'],
                ['name' => 'Fatura & Tahsilat', 'color' => '#10b981', 'icon' => '💰'],
                ['name' => 'Teklif & Satış', 'color' => '#f59e0b', 'icon' => '📄'],
                ['name' => 'Saha Ziyareti', 'color' => '#ec4899', 'icon' => '🚗'],
            ];

            foreach ($types as $t) {
                QueryBuilder::table('crm_task_types', $pdo)->insert([
                    'name' => $t['name'],
                    'color' => $t['color'],
                    'icon' => $t['icon'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Seed Sample Expense in crm_invoices if no expense exists
        $hasExpense = QueryBuilder::table('crm_invoices', $pdo)->where('type', '=', 'expense')->count();
        if ($hasExpense === 0) {
            $contact = QueryBuilder::table('entity_records', $pdo)->first();
            $contactId = $contact ? (int) $contact['id'] : 1;

            QueryBuilder::table('crm_invoices', $pdo)->insert([
                'invoice_number' => 'EXP-2026-001',
                'type' => 'expense',
                'category' => 'Sunucu & Bulut Altyapısı',
                'contact_id' => $contactId,
                'total_amount' => 4500.00,
                'status' => 'paid',
                'due_date' => date('Y-m-d'),
                'items' => json_encode([
                    ['description' => 'AWS Bulut Sunucu & Veritabanı Maliyeti', 'hours' => 1, 'unit_price' => 4500, 'total' => 4500]
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("DROP TABLE IF EXISTS crm_task_types");
    }
}
;