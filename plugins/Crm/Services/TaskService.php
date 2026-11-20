<?php

declare(strict_types=1);

namespace Plugins\Crm\Services;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use App\Core\Exceptions\ValidationException;
use PDO;

final class TaskService
{
    private PDO $pdo;

    private NotificationService $notificationService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->notificationService = new NotificationService($this->pdo);
    }

    public function getTaskTypes(): array
    {
        return QueryBuilder::table('crm_task_types', $this->pdo)->orderBy('id', 'ASC')->get();
    }

    public function getTaskTypeById(int $id): ?array
    {
        return QueryBuilder::table('crm_task_types', $this->pdo)->find($id);
    }

    public function createTaskType(string $name, string $color = '#2563eb', string $icon = '📋'): int
    {
        if (trim($name) === '') {
            throw new ValidationException('Görev türü adı zorunludur.');
        }

        return QueryBuilder::table('crm_task_types', $this->pdo)->insert([
            'name' => trim($name),
            'color' => trim($color) ?: '#2563eb',
            'icon' => trim($icon) ?: '📋',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteTaskType(int $id): bool
    {
        return QueryBuilder::table('crm_task_types', $this->pdo)->where('id', '=', $id)->delete() > 0;
    }

    public function updateTaskType(int $id, string $name, string $color = '#2563eb', string $icon = '📋'): bool
    {
        if (trim($name) === '') {
            throw new ValidationException('Görev türü adı zorunludur.');
        }
        $affected = QueryBuilder::table('crm_task_types', $this->pdo)
            ->where('id', '=', $id)
            ->update([
                'name' => trim($name),
                'color' => trim($color) ?: '#2563eb',
                'icon' => trim($icon) ?: '📋',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        return $affected > 0;
    }

    public function getAllTasks(array $filters = []): array
    {
        $query = QueryBuilder::table('crm_tasks', $this->pdo);

        if (!empty($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', '=', $filters['priority']);
        }
        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', '=', (int) $filters['contact_id']);
        }
        if (!empty($filters['task_type_id'])) {
            $query->where('task_type_id', '=', (int) $filters['task_type_id']);
        }

        $tasks = $query->orderBy('id', 'DESC')->get();
        $contactService = new ContactService(null, $this->pdo);
        $taskTypes = $this->getTaskTypesMap();

        foreach ($tasks as &$task) {
            if (!empty($task['contact_id'])) {
                $contact = $contactService->getContactById((int) $task['contact_id']);
                $task['contact_name'] = $contact['name'] ?? null;
            } else {
                $task['contact_name'] = null;
            }

            $task['task_type'] = $taskTypes[(int) ($task['task_type_id'] ?? 0)] ?? null;
        }

        return $tasks;
    }

    public function getTaskById(int $id): ?array
    {
        $task = QueryBuilder::table('crm_tasks', $this->pdo)->find($id);
        if ($task === null) {
            return null;
        }

        if (!empty($task['contact_id'])) {
            $contact = (new ContactService(null, $this->pdo))->getContactById((int) $task['contact_id']);
            $task['contact_name'] = $contact['name'] ?? null;
        }

        $taskTypes = $this->getTaskTypesMap();
        $task['task_type'] = $taskTypes[(int) ($task['task_type_id'] ?? 0)] ?? null;

        return $task;
    }

    public function createTask(array $data): int
    {
        if (empty($data['title'])) {
            throw new ValidationException('Görev başlığı (title) zorunludur.');
        }

        $fee = isset($data['fee']) ? (float) $data['fee'] : 0.00;

        $id = QueryBuilder::table('crm_tasks', $this->pdo)->insert([
            'title' => trim((string) $data['title']),
            'description' => trim((string) ($data['description'] ?? '')),
            'contact_id' => !empty($data['contact_id']) ? (int) $data['contact_id'] : null,
            'task_type_id' => !empty($data['task_type_id']) ? (int) $data['task_type_id'] : null,
            'fee' => $fee,
            'due_date' => !empty($data['due_date']) ? (string) $data['due_date'] : date('Y-m-d'),
            'status' => (string) ($data['status'] ?? 'pending'),
            'priority' => (string) ($data['priority'] ?? 'medium'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notificationService->createNotification(
            'Yeni Görev Atandı',
            sprintf('"%s" görevi takvime eklendi (Son Tarih: %s).', $data['title'], $data['due_date'] ?? '—'),
            '/admin/crm/tasks',
            'task'
        );

        // Auto invoice to pre-accounting if fee > 0 and auto_invoice is checked or contact is selected!
        if ($fee > 0 && (!empty($data['auto_invoice']) || !empty($data['contact_id']))) {
            $financeService = new FinanceService($this->pdo);
            $financeService->createInvoice([
                'invoice_number' => 'INV-TASK-' . date('Ymd') . '-' . rand(100, 999),
                'type' => 'income',
                'contact_id' => !empty($data['contact_id']) ? (int) $data['contact_id'] : 1,
                'total_amount' => $fee,
                'status' => 'unpaid',
                'due_date' => !empty($data['due_date']) ? (string) $data['due_date'] : date('Y-m-d'),
                'items' => [
                    [
                        'description' => '[Görev Ücreti] ' . trim((string) $data['title']),
                        'hours' => 1,
                        'unit_price' => $fee,
                        'total' => $fee,
                    ]
                ],
            ]);
        }

        return $id;
    }

    public function updateTask(int $id, array $data): bool
    {
        $existing = $this->getTaskById($id);
        if ($existing === null) {
            throw new ValidationException(sprintf('Görev #%d bulunamadı.', $id));
        }

        $fee = isset($data['fee']) ? (float) $data['fee'] : (float) $existing['fee'];

        $affected = QueryBuilder::table('crm_tasks', $this->pdo)
            ->where('id', '=', $id)
            ->update([
                'title' => trim((string) ($data['title'] ?? $existing['title'])),
                'description' => trim((string) ($data['description'] ?? $existing['description'])),
                'contact_id' => isset($data['contact_id']) ? (!empty($data['contact_id']) ? (int) $data['contact_id'] : null) : $existing['contact_id'],
                'task_type_id' => isset($data['task_type_id']) ? (!empty($data['task_type_id']) ? (int) $data['task_type_id'] : null) : $existing['task_type_id'],
                'fee' => $fee,
                'due_date' => isset($data['due_date']) ? (!empty($data['due_date']) ? (string) $data['due_date'] : null) : $existing['due_date'],
                'status' => (string) ($data['status'] ?? $existing['status']),
                'priority' => (string) ($data['priority'] ?? $existing['priority']),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    public function toggleTaskStatus(int $id): bool
    {
        $task = $this->getTaskById($id);
        if ($task === null) {
            return false;
        }

        $newStatus = $task['status'] === 'completed' ? 'pending' : 'completed';

        $affected = QueryBuilder::table('crm_tasks', $this->pdo)
            ->where('id', '=', $id)
            ->update([
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    public function deleteTask(int $id): bool
    {
        return QueryBuilder::table('crm_tasks', $this->pdo)->where('id', '=', $id)->delete() > 0;
    }

    public function getGoogleCalendarGrid(int $year, int $month): array
    {
        $firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = (int) date('t', strtotime($firstDayOfMonth));
        $dayOfWeekFirst = (int) date('N', strtotime($firstDayOfMonth)); // 1 (Mon) to 7 (Sun)

        $startGridDate = date('Y-m-d', strtotime($firstDayOfMonth . ' -' . ($dayOfWeekFirst - 1) . ' days'));
        $totalGridDays = 42; // 6 weeks * 7 days

        $endGridDate = date('Y-m-d', strtotime($startGridDate . ' +' . ($totalGridDays - 1) . ' days'));

        $tasks = $this->getAllTasks();
        $tasksByDate = [];

        foreach ($tasks as $task) {
            if (!empty($task['due_date'])) {
                $dateKey = date('Y-m-d', strtotime($task['due_date']));
                if (!isset($tasksByDate[$dateKey])) {
                    $tasksByDate[$dateKey] = [];
                }
                $tasksByDate[$dateKey][] = $task;
            }
        }

        $weeks = [];
        $currentDate = $startGridDate;

        for ($w = 0; $w < 6; $w++) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $isCurrentMonth = (date('m', strtotime($currentDate)) == sprintf('%02d', $month));
                $isToday = ($currentDate === date('Y-m-d'));

                $week[] = [
                    'date' => $currentDate,
                    'day_number' => (int) date('d', strtotime($currentDate)),
                    'is_current_month' => $isCurrentMonth,
                    'is_today' => $isToday,
                    'tasks' => $tasksByDate[$currentDate] ?? [],
                ];

                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
            $weeks[] = $week;
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $this->getTurkishMonthName($month),
            'weeks' => $weeks,
            'prev_year' => $month === 1 ? $year - 1 : $year,
            'prev_month' => $month === 1 ? 12 : $month - 1,
            'next_year' => $month === 12 ? $year + 1 : $year,
            'next_month' => $month === 12 ? 1 : $month + 1,
        ];
    }

    private function getTaskTypesMap(): array
    {
        $types = $this->getTaskTypes();
        $map = [];
        foreach ($types as $t) {
            $map[(int) $t['id']] = $t;
        }
        return $map;
    }

    private function getTurkishMonthName(int $month): string
    {
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];
        return $months[$month] ?? '';
    }
}
