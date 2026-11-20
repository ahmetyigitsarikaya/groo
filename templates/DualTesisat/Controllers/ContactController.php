<?php

declare(strict_types=1);

namespace Templates\DualTesisat\Controllers;

use App\Core\Database\Connection;
use App\Core\View\View;
use Plugins\Traffic\Helpers\BotDetector;

final class ContactController
{
    public function show(array $params = []): void
    {
        View::make('pages/contact')->display();
    }

    public function submit(array $params = []): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? '')); // Honeypot field
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $success = false;
        $responseMsg = 'Lütfen gerekli alanları (Ad, E-posta, Mesaj) doldurunuz.';

        // 1. Bot Control (Honeypot)
        if ($website !== '') {
            // Honeypot filled by bot
            View::make('pages/contact', ['success' => false, 'message' => 'Geçersiz istek.'])->display();
            return;
        }

        // 2. Bot Control (User Agent checking via BotDetector)
        if (class_exists(BotDetector::class)) {
            $detector = new BotDetector($userAgent);
            if ($detector->isBot()) {
                View::make('pages/contact', ['success' => false, 'message' => 'Bot trafiği tespit edildi.'])->display();
                return;
            }
        }

        if ($name !== '' && $email !== '' && $message !== '') {
            try {
                $pdo = Connection::getInstance();
                
                // 3. Rate Limiting (e.g., max 3 messages per hour from same IP)
                $stmtLimit = $pdo->prepare("SELECT COUNT(*) FROM dual_contact_messages WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $stmtLimit->execute([$ip]);
                if ((int) $stmtLimit->fetchColumn() >= 3) {
                    View::make('pages/contact', [
                        'success' => false,
                        'message' => 'Saatlik mesaj gönderme sınırına ulaştınız. Lütfen daha sonra tekrar deneyin.'
                    ])->display();
                    return;
                }

                $stmt = $pdo->prepare("INSERT INTO dual_contact_messages (name, phone, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $subject, $message, $ip]);
                $success = true;
                $responseMsg = 'Mesajınız başarıyla iletildi. En kısa sürede dönüş yapacağız.';
            } catch (\Throwable $e) {
                $responseMsg = 'Mesaj gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
                error_log("Contact form error: " . $e->getMessage());
            }
        }
        
        View::make('pages/contact', [
            'success' => $success,
            'message' => $responseMsg
        ])->display();
    }
}
