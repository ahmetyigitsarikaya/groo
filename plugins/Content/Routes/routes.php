<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\Content\Controllers\AdminPostController;
use Plugins\Content\Controllers\ApiPostController;

/** @var Router $router */

// Admin Routes (Protected by AdminAuthMiddleware)
$router->group('/admin/posts', static function (Router $router): void {
    $router->get('/', [AdminPostController::class, 'index']);
    $router->get('/create', [AdminPostController::class, 'create']);
    $router->post('/create', [AdminPostController::class, 'store']);
    $router->get('/{id:\d+}/edit', [AdminPostController::class, 'edit']);
    $router->post('/{id:\d+}/edit', [AdminPostController::class, 'update']);
    $router->post('/{id:\d+}/delete', [AdminPostController::class, 'destroy']);
}, [new AdminAuthMiddleware()]);

// API Routes (Publicly Accessible)
$router->group('/api/content/posts', static function (Router $router): void {
    $router->get('/', [ApiPostController::class, 'index']);
    $router->get('/{slug}', [ApiPostController::class, 'show']);
});
