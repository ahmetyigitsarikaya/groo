<?php

declare(strict_types=1);

namespace Plugins\Crm\Services;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use PDO;

final class ActivityService
{
    private PDO $pdo;

    private NotificationService $notificationService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->notificationService = new NotificationService($this->pdo);
    }

    public function addNote(int $contactId, string $note, string $type = 'note', ?int $userId = null): int
    {
        $id = QueryBuilder::table('crm_notes', $this->pdo)->insert([
            'contact_id' => $contactId,
            'user_id' => $userId,
            'note' => trim($note),
            'activity_type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $contactService = new ContactService(null, $this->pdo);
        $contact = $contactService->getContactById($contactId);
        $contactName = $contact['name'] ?? ('#' . $contactId);

        $this->notificationService->createNotification(
            'Müşteriye Yeni Not Eklendi',
            sprintf('"%s" carisine yeni %s kaydı eklendi.', $contactName, $type),
            '/admin/crm/contacts/' . $contactId,
            'activity'
        );

        return $id;
    }

    public function getContactNotes(int $contactId): array
    {
        return QueryBuilder::table('crm_notes', $this->pdo)
            ->where('contact_id', '=', $contactId)
            ->orderBy('id', 'DESC')
            ->get();
    }
}
