<?php

declare(strict_types=1);

namespace Plugins\Crm\Services;

use App\Core\Database\Connection;
use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;
use App\Core\Exceptions\ValidationException;
use PDO;

final class ContactService
{
    private DynamicEntityRepository $repository;

    private PDO $pdo;

    private string $entitySlug = 'crm_contact';

    private NotificationService $notificationService;

    public function __construct(?DynamicEntityRepository $repository = null, ?PDO $pdo = null)
    {
        $this->repository = $repository ?? new DynamicEntityRepository();
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->notificationService = new NotificationService($this->pdo);
    }

    public function getAllContacts(): array
    {
        $records = $this->repository->allRecords($this->entitySlug);
        $contacts = [];

        foreach ($records as $record) {
            $contacts[] = $this->formatContact($record);
        }

        return $contacts;
    }

    public function getContactById(int $id): ?array
    {
        $record = $this->repository->findRecord($id);
        if ($record === null) {
            return null;
        }

        $entity = $this->repository->requireEntity($this->entitySlug);
        if ((int) $record['entity_id'] !== (int) $entity['id']) {
            return null;
        }

        return $this->formatContact($record);
    }

    public function createContact(array $data): int
    {
        $sanitized = $this->prepareData($data);

        if (empty($sanitized['name'])) {
            throw new ValidationException('Müşteri/Unvan (name) alanı zorunludur.');
        }

        $id = $this->repository->createRecord($this->entitySlug, $sanitized);

        $this->notificationService->createNotification(
            'Yeni Müşteri Oluşturuldu',
            sprintf('"%s" cari kaydı sisteme eklendi.', $sanitized['name']),
            '/admin/crm/contacts/' . $id,
            'contact'
        );

        return $id;
    }

    public function updateContact(int $id, array $data): bool
    {
        $existing = $this->getContactById($id);
        if ($existing === null) {
            throw new ValidationException(sprintf('Müşteri #%d bulunamadı.', $id));
        }

        $sanitized = $this->prepareData($data);

        return $this->repository->updateRecord($id, $sanitized);
    }

    public function deleteContact(int $id): bool
    {
        $entity = $this->repository->requireEntity($this->entitySlug);

        $affected = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS, $this->pdo)
            ->where('id', '=', $id)
            ->where('entity_id', '=', (int) $entity['id'])
            ->delete();

        return $affected > 0;
    }

    public function getContactTimeline(int $contactId): array
    {
        $timeline = [];

        // 1. Notes & Activities
        $notes = QueryBuilder::table('crm_notes', $this->pdo)
            ->where('contact_id', '=', $contactId)
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($notes as $note) {
            $typeLabel = match ($note['activity_type'] ?? 'note') {
                'call' => '📞 Telefon Görüşmesi',
                'meeting' => '🤝 Toplantı / Görüşme',
                'email' => '✉️ E-posta İletişimi',
                default => '📝 Görüşme Notu',
            };

            $timeline[] = [
                'type' => 'activity',
                'title' => $typeLabel,
                'content' => $note['note'],
                'date' => $note['created_at'],
                'badge' => 'info',
            ];
        }

        // 2. Tasks
        $tasks = QueryBuilder::table('crm_tasks', $this->pdo)
            ->where('contact_id', '=', $contactId)
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($tasks as $task) {
            $statusStr = $task['status'] === 'completed' ? 'Tamamlandı' : 'Bekliyor';
            $timeline[] = [
                'type' => 'task',
                'title' => '📋 Görev: ' . $task['title'],
                'content' => ($task['description'] ?? '') . ' (Son Tarih: ' . ($task['due_date'] ?? '—') . ' - Durum: ' . $statusStr . ')',
                'date' => $task['created_at'],
                'badge' => $task['status'] === 'completed' ? 'success' : 'warning',
            ];
        }

        // 3. Invoices
        $invoices = QueryBuilder::table('crm_invoices', $this->pdo)
            ->where('contact_id', '=', $contactId)
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($invoices as $inv) {
            $statusStr = match ($inv['status']) {
                'paid' => 'Ödendi',
                'overdue' => 'Gecikmede',
                default => 'Ödeme Bekliyor',
            };

            $timeline[] = [
                'type' => 'invoice',
                'title' => '💰 Fatura: ' . $inv['invoice_number'],
                'content' => 'Tutar: ' . number_format((float) $inv['total_amount'], 2, ',', '.') . ' ₺ (Durum: ' . $statusStr . ')',
                'date' => $inv['created_at'],
                'badge' => $inv['status'] === 'paid' ? 'success' : ($inv['status'] === 'overdue' ? 'danger' : 'primary'),
            ];
        }

        // Sort chronological DESC
        usort($timeline, static function (array $a, array $b): int {
            return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        });

        return $timeline;
    }

    private function prepareData(array $data): array
    {
        $allowed = ['name', 'type', 'email', 'phone', 'address', 'status', 'tags', 'custom_fields'];
        $prepared = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $prepared[$field] = $data[$field];
            }
        }

        $prepared['type'] = $prepared['type'] ?? 'b2b';
        $prepared['status'] = $prepared['status'] ?? 'lead';

        if (isset($prepared['tags']) && is_string($prepared['tags'])) {
            $prepared['tags'] = array_values(array_filter(array_map('trim', explode(',', $prepared['tags']))));
        }

        if (isset($prepared['custom_fields']) && is_string($prepared['custom_fields'])) {
            $decoded = json_decode($prepared['custom_fields'], true);
            $prepared['custom_fields'] = is_array($decoded) ? $decoded : [];
        }

        return $prepared;
    }

    private function formatContact(array $record): array
    {
        $data = $record['data'] ?? [];

        return array_merge([
            'id' => (int) $record['id'],
            'name' => '',
            'type' => 'b2b',
            'email' => '',
            'phone' => '',
            'address' => '',
            'status' => 'lead',
            'tags' => [],
            'custom_fields' => [],
            'created_at' => $record['created_at'] ?? null,
            'updated_at' => $record['updated_at'] ?? null,
        ], $data, ['id' => (int) $record['id']]);
    }
}
