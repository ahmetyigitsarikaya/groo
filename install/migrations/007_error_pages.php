<?php
declare(strict_types=1);

final class Install_008_error_pages
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS error_pages (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                error_code INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                image_url VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_error_code (error_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $stmt = $pdo->query('SELECT COUNT(*) FROM error_pages');
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("
                INSERT INTO error_pages (error_code, title, message) VALUES
                (404, 'Sayfa Bulunamadı', 'Aradığınız sayfa silinmiş veya taşınmış olabilir.'),
                (403, 'Erişim Reddedildi', 'Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.'),
                (500, 'Sunucu Hatası', 'Sistemde geçici bir sorun oluştu. Lütfen daha sonra tekrar deneyin.')
            ");
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS error_pages');
    }
}
