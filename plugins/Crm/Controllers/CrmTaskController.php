<?php

declare(strict_types=1);

namespace Plugins\Crm\Controllers;

use App\Core\Exceptions\ValidationException;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;
use Plugins\Crm\Services\ContactService;
use Plugins\Crm\Services\TaskService;
use Throwable;

final class CrmTaskController extends BaseController
{
    private TaskService $taskService;

    private ContactService $contactService;

    public function __construct(?TaskService $taskService = null, ?ContactService $contactService = null)
    {
        parent::__construct();
        $this->taskService = $taskService ?? new TaskService();
        $this->contactService = $contactService ?? new ContactService();
    }

    public function index(array $params = []): void
    {
        $status = $_GET['status'] ?? null;
        $taskTypeId = isset($_GET['task_type_id']) ? (int) $_GET['task_type_id'] : null;

        $filters = [];
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        if (!empty($taskTypeId)) {
            $filters['task_type_id'] = $taskTypeId;
        }

        $tasks = $this->taskService->getAllTasks($filters);

        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');

        $calendarGrid = $this->taskService->getGoogleCalendarGrid($year, $month);
        $taskTypes = $this->taskService->getTaskTypes();

        $this->render('admin/crm/tasks/index', [
            'tasks' => $tasks,
            'calendar_grid' => $calendarGrid,
            'task_types' => $taskTypes,
            'current_status' => $status,
            'current_task_type' => $taskTypeId,
        ]);
    }

    public function create(array $params = []): void
    {
        $contacts = $this->contactService->getAllContacts();
        $taskTypes = $this->taskService->getTaskTypes();

        $this->render('admin/crm/tasks/form', [
            'task' => null,
            'contacts' => $contacts,
            'task_types' => $taskTypes,
            'is_edit' => false,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireCsrf();

        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'contact_id' => !empty($_POST['contact_id']) ? (int) $_POST['contact_id'] : null,
            'task_type_id' => !empty($_POST['task_type_id']) ? (int) $_POST['task_type_id'] : null,
            'fee' => (float) ($_POST['fee'] ?? 0),
            'auto_invoice' => !empty($_POST['auto_invoice']),
            'due_date' => trim((string) ($_POST['due_date'] ?? date('Y-m-d'))),
            'status' => (string) ($_POST['status'] ?? 'pending'),
            'priority' => (string) ($_POST['priority'] ?? 'medium'),
        ];

        try {
            $this->taskService->createTask($data);
            Flash::set('success', 'Görev ve planlama başarıyla oluşturuldu.');
            $this->redirect('/admin/crm/tasks');
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $this->render('admin/crm/tasks/form', [
                'task' => $data,
                'contacts' => $this->contactService->getAllContacts(),
                'task_types' => $this->taskService->getTaskTypes(),
                'is_edit' => false,
            ]);
        } catch (Throwable $e) {
            Flash::set('error', 'Hata oluştu: ' . $e->getMessage());
            $this->render('admin/crm/tasks/form', [
                'task' => $data,
                'contacts' => $this->contactService->getAllContacts(),
                'task_types' => $this->taskService->getTaskTypes(),
                'is_edit' => false,
            ]);
        }
    }

    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $task = $this->taskService->getTaskById($id);

        if ($task === null) {
            Flash::set('error', 'Görev bulunamadı.');
            $this->redirect('/admin/crm/tasks');
        }

        $contacts = $this->contactService->getAllContacts();
        $taskTypes = $this->taskService->getTaskTypes();

        $this->render('admin/crm/tasks/form', [
            'task' => $task,
            'contacts' => $contacts,
            'task_types' => $taskTypes,
            'is_edit' => true,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $task = $this->taskService->getTaskById($id);

        if ($task === null) {
            Flash::set('error', 'Görev bulunamadı.');
            $this->redirect('/admin/crm/tasks');
        }

        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'contact_id' => !empty($_POST['contact_id']) ? (int) $_POST['contact_id'] : null,
            'task_type_id' => !empty($_POST['task_type_id']) ? (int) $_POST['task_type_id'] : null,
            'fee' => (float) ($_POST['fee'] ?? 0),
            'due_date' => trim((string) ($_POST['due_date'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'pending'),
            'priority' => (string) ($_POST['priority'] ?? 'medium'),
        ];

        try {
            $this->taskService->updateTask($id, $data);
            Flash::set('success', 'Görev bilgileri güncellendi.');
            $this->redirect('/admin/crm/tasks');
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $data['id'] = $id;
            $this->render('admin/crm/tasks/form', [
                'task' => $data,
                'contacts' => $this->contactService->getAllContacts(),
                'task_types' => $this->taskService->getTaskTypes(),
                'is_edit' => true,
            ]);
        }
    }

    public function toggleStatus(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->taskService->toggleTaskStatus($id);

        Flash::set('success', 'Görev durumu güncellendi.');
        $this->redirect('/admin/crm/tasks');
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->taskService->deleteTask($id);

        Flash::set('success', 'Görev silindi.');
        $this->redirect('/admin/crm/tasks');
    }

    // Task Types Management
    public function taskTypesIndex(array $params = []): void
    {
        $types = $this->taskService->getTaskTypes();

        $this->render('admin/crm/tasks/task_types', [
            'types' => $types,
        ]);
    }

    public function taskTypesStore(array $params = []): void
    {
        $this->requireCsrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $color = trim((string) ($_POST['color'] ?? '#2563eb'));
        $icon = trim((string) ($_POST['icon'] ?? '📋'));

        try {
            $this->taskService->createTaskType($name, $color, $icon);
            Flash::set('success', 'Yeni görev türü / kategorisi eklendi.');
        } catch (ValidationException $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/crm/task-types');
    }

    public function taskTypesDestroy(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->taskService->deleteTaskType($id);

        Flash::set('success', 'Görev türü silindi.');
        $this->redirect('/admin/crm/task-types');
    }

    public function taskTypesCreate(array $params = []): void
    {
        $this->render('admin/crm/tasks/task_type_form', [
            'type' => null,
            'is_edit' => false,
        ]);
    }

    public function taskTypesEdit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        
        // Find the task type. We need to get all task types and find the one.
        $types = $this->taskService->getTaskTypes();
        $type = null;
        foreach ($types as $t) {
            if ((int) $t['id'] === $id) {
                $type = $t;
                break;
            }
        }

        if ($type === null) {
            Flash::set('error', 'Görev türü bulunamadı.');
            $this->redirect('/admin/crm/task-types');
        }

        $this->render('admin/crm/tasks/task_type_form', [
            'type' => $type,
            'is_edit' => true,
        ]);
    }

    public function taskTypesUpdate(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $color = trim((string) ($_POST['color'] ?? '#2563eb'));
        $icon = trim((string) ($_POST['icon'] ?? '📋'));

        try {
            $this->taskService->updateTaskType($id, $name, $color, $icon);
            Flash::set('success', 'Görev türü güncellendi.');
        } catch (ValidationException $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/crm/task-types');
    }
}
