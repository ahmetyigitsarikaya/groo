<?php

declare(strict_types=1);

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        $repository = new DynamicEntityRepository($pdo);

        // 1. Dynamic Entity: crm_contact
        if ($repository->getEntity('crm_contact') === null) {
            $repository->createEntity('crm_contact', 'CRM Müşteri / Kişi', 'CRM Müşteri, Lead ve Cari yönetimi.');
            $repository->addAttribute('crm_contact', 'name', 'string', true);
            $repository->addAttribute('crm_contact', 'type', 'string', true, ['choices' => ['b2b', 'b2c']]);
            $repository->addAttribute('crm_contact', 'email', 'string', false);
            $repository->addAttribute('crm_contact', 'phone', 'string', false);
            $repository->addAttribute('crm_contact', 'address', 'string', false);
            $repository->addAttribute('crm_contact', 'status', 'string', true, ['choices' => ['lead', 'customer', 'passive']]);
            $repository->addAttribute('crm_contact', 'tags', 'json', false);
            $repository->addAttribute('crm_contact', 'custom_fields', 'json', false);
        }

        // 2. crm_notes table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS crm_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contact_id INT NOT NULL,
                user_id INT NULL,
                note TEXT NOT NULL,
                activity_type VARCHAR(50) NOT NULL DEFAULT 'note',
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 3. crm_tasks table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS crm_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                contact_id INT NULL,
                due_date DATE NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                priority VARCHAR(50) NOT NULL DEFAULT 'medium',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 4. crm_invoices table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS crm_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number VARCHAR(100) NOT NULL,
                contact_id INT NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
                due_date DATE NULL,
                items JSON NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 5. crm_notifications table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS crm_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                type VARCHAR(50) NOT NULL DEFAULT 'general',
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed Sample Records if empty
        $contactEntity = $repository->getEntity('crm_contact');
        if ($contactEntity !== null) {
            $hasContacts = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS, $pdo)
                ->where('entity_id', '=', (int) $contactEntity['id'])
                ->count();

            if ($hasContacts === 0) {
                $contactId1 = $repository->createRecord('crm_contact', [
                    'name' => 'Acme Holding A.Ş.',
                    'type' => 'b2b',
                    'email' => 'info@acme.com',
                    'phone' => '+90 212 555 0101',
                    'address' => 'Levent Mah. Büyükdere Cad. No:100 İstanbul',
                    'status' => 'customer',
                    'tags' => ['VIP', 'Kurumsal', 'Yazılım'],
                    'custom_fields' => ['vergi_dairesi' => 'Zincirlikuyu', 'vergi_no' => '1234567890'],
                ]);

                $contactId2 = $repository->createRecord('crm_contact', [
                    'name' => 'Ahmet Yılmaz (Teknoloji Lideri)',
                    'type' => 'b2c',
                    'email' => 'ahmet@yilmaz.com',
                    'phone' => '+90 532 555 0202',
                    'address' => 'Kadıköy / İstanbul',
                    'status' => 'lead',
                    'tags' => ['Potansiyel', 'Mobil'],
                    'custom_fields' => [],
                ]);

                // Sample Notes
                $pdo->prepare("INSERT INTO crm_notes (contact_id, user_id, note, activity_type, created_at) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$contactId1, 1, 'Müşteri ile yıllık yazılım ve ek hizmet sözleşmesi görüşüldü.', 'meeting', date('Y-m-d H:i:s')]);

                // Sample Tasks
                $pdo->prepare("INSERT INTO crm_tasks (title, description, contact_id, due_date, status, priority, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(['Yıllık Destek Sözleşmesi İmzalanacak', 'Acme Holding ile ek süre teklifi revize edilip gönderilecek.', $contactId1, date('Y-m-d'), 'pending', 'high', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);

                $pdo->prepare("INSERT INTO crm_tasks (title, description, contact_id, due_date, status, priority, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(['Mobil Uygulama Teklifi Sunumu', 'Ahmet Yılmaz için hazırlanan demo takdim edilecek.', $contactId2, date('Y-m-d', strtotime('+2 days')), 'pending', 'medium', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);

                // Sample Invoice (with Billable Hours / Service Items)
                $itemsJson = json_encode([
                    ['description' => 'Eklenti Geliştirme Hizmeti', 'hours' => 10, 'unit_price' => 1500, 'total' => 15000],
                    ['description' => 'Ek Süre / Canlıya Alma ve Destek', 'hours' => 5, 'unit_price' => 1000, 'total' => 5000],
                ], JSON_UNESCAPED_UNICODE);

                $pdo->prepare("INSERT INTO crm_invoices (invoice_number, contact_id, total_amount, status, due_date, items, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute(['INV-2026-001', $contactId1, 20000.00, 'unpaid', date('Y-m-d', strtotime('+7 days')), $itemsJson, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);

                // Sample Notification
                $pdo->prepare("INSERT INTO crm_notifications (user_id, title, message, link, is_read, type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([null, 'Yeni CRM Müşterisi Ekledi', 'Acme Holding A.Ş. cari kaydı oluşturuldu.', '/admin/crm/contacts/' . $contactId1, 0, 'contact', date('Y-m-d H:i:s')]);
            }
        }
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        $pdo->exec("DROP TABLE IF EXISTS crm_notes");
        $pdo->exec("DROP TABLE IF EXISTS crm_tasks");
        $pdo->exec("DROP TABLE IF EXISTS crm_invoices");
        $pdo->exec("DROP TABLE IF EXISTS crm_notifications");

        QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES, $pdo)
            ->where('slug', '=', 'crm_contact')
            ->delete();
    }
}
;