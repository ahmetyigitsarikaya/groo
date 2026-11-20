<?php

declare(strict_types=1);

namespace Plugins\Crm\Controllers;

use App\Core\Http\ApiResponse;
use Plugins\Crm\Services\NotificationService;

final class CrmNotificationController
{
    private NotificationService $notificationService;

    public function __construct(?NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    public function markRead(array $params = []): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw !== false ? $raw : '', true) ?: $_POST;
        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {
            $this->notificationService->markAsRead($id);
        }

        ApiResponse::success(['id' => $id, 'unread_count' => $this->notificationService->getUnreadCount()], 'Okundu işaretlendi.')->send();
    }

    public function markAllRead(array $params = []): void
    {
        $this->notificationService->markAllAsRead();

        ApiResponse::success(['unread_count' => 0], 'Tüm bildirimler okundu işaretlendi.')->send();
    }
}
