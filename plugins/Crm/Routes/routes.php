<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\Crm\Controllers\CrmContactController;
use Plugins\Crm\Controllers\CrmFinanceController;
use Plugins\Crm\Controllers\CrmNotificationController;
use Plugins\Crm\Controllers\CrmTaskController;

/** @var Router $router */

$router->group('/admin/crm', static function (Router $router): void {
    $router->get('/', function() {
        $base = rtrim((string) (\App\Core\Config::get('app.base_path') ?? ''), '/');
        header('Location: ' . $base . '/admin/crm/contacts', true, 302);
        exit;
    });
    $router->get('', function() {
        $base = rtrim((string) (\App\Core\Config::get('app.base_path') ?? ''), '/');
        header('Location: ' . $base . '/admin/crm/contacts', true, 302);
        exit;
    });

    // Direct route for task types list (fallback if nested group fails)
    $router->get('/task-types', [CrmTaskController::class, 'taskTypesIndex']);
    // 1. Contacts (Müşteri Yönetimi & Zaman Tüneli)
    $router->get('/contacts', [CrmContactController::class, 'index']);
    $router->get('/contacts/create', [CrmContactController::class, 'create']);
    $router->post('/contacts/create', [CrmContactController::class, 'store']);
    $router->get('/contacts/{id:\d+}', [CrmContactController::class, 'show']);
    $router->get('/contacts/{id:\d+}/edit', [CrmContactController::class, 'edit']);
    $router->post('/contacts/{id:\d+}/edit', [CrmContactController::class, 'update']);
    $router->post('/contacts/{id:\d+}/notes', [CrmContactController::class, 'addNote']);
    $router->post('/contacts/{id:\d+}/delete', [CrmContactController::class, 'destroy']);

    // 2. Tasks & Calendar (Görevler & Google Takvim)
    $router->get('/tasks', [CrmTaskController::class, 'index']);
    $router->get('/tasks/create', [CrmTaskController::class, 'create']);
    $router->post('/tasks/create', [CrmTaskController::class, 'store']);
    $router->get('/tasks/{id:\d+}/edit', [CrmTaskController::class, 'edit']);
    $router->post('/tasks/{id:\d+}/edit', [CrmTaskController::class, 'update']);
    $router->post('/tasks/{id:\d+}/toggle', [CrmTaskController::class, 'toggleStatus']);
    $router->post('/tasks/{id:\d+}/delete', [CrmTaskController::class, 'destroy']);

    // Task Types Management
    $router->get('/task-types', [CrmTaskController::class, 'taskTypesIndex']);
    $router->post('/task-types/create', [CrmTaskController::class, 'taskTypesStore']);
    $router->post('/task-types/{id:\d+}/delete', [CrmTaskController::class, 'taskTypesDestroy']);
    // Task Types CRUD
    $router->get('/task-types/create', [CrmTaskController::class, 'taskTypesCreate']);
    $router->get('/task-types/{id:\d+}/edit', [CrmTaskController::class, 'taskTypesEdit']);
    $router->post('/task-types/{id:\d+}/edit', [CrmTaskController::class, 'taskTypesUpdate']);

    // 3. Finance & Invoices & Expenses (Ön Muhasebe & Gelir-Gider)
    $router->get('/finance', [CrmFinanceController::class, 'index']);
    $router->get('/finance/create', [CrmFinanceController::class, 'create']);
    $router->post('/finance/create', [CrmFinanceController::class, 'store']);
    $router->get('/finance/expense/create', [CrmFinanceController::class, 'createExpense']);
    $router->get('/finance/{id:\d+}', [CrmFinanceController::class, 'show']);
    $router->post('/finance/{id:\d+}/pay', [CrmFinanceController::class, 'markPaid']);
    $router->post('/finance/{id:\d+}/delete', [CrmFinanceController::class, 'destroy']);

    // 4. Notifications API
    $router->post('/notifications/mark-read', [CrmNotificationController::class, 'markRead']);
    $router->post('/notifications/mark-all-read', [CrmNotificationController::class, 'markAllRead']);
}, [new AdminAuthMiddleware()]);
