<?php

declare(strict_types=1);

namespace Plugins\Crm\Services;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use PDO;

final class NotificationService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    public function createNotification(string $title, string $message, ?string $link = null, string $type = 'general', ?int $userId = null): int
    {
        return QueryBuilder::table('crm_notifications', $this->pdo)->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getUnreadNotifications(?int $userId = null, int $limit = 10): array
    {
        $query = QueryBuilder::table('crm_notifications', $this->pdo)
            ->where('is_read', '=', 0);

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        return $query->orderBy('id', 'DESC')->limit($limit)->get();
    }

    public function getUnreadCount(?int $userId = null): int
    {
        $query = QueryBuilder::table('crm_notifications', $this->pdo)
            ->where('is_read', '=', 0);

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        return $query->count();
    }

    public function markAsRead(int $id): bool
    {
        $affected = QueryBuilder::table('crm_notifications', $this->pdo)
            ->where('id', '=', $id)
            ->update(['is_read' => 1]);

        return $affected > 0;
    }

    public function markAllAsRead(?int $userId = null): bool
    {
        $query = QueryBuilder::table('crm_notifications', $this->pdo)
            ->where('is_read', '=', 0);

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        return $query->update(['is_read' => 1]) > 0;
    }
}
