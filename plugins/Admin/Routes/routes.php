<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Controllers\AssetController;
use Plugins\Admin\Controllers\AuthController;
use Plugins\Admin\Controllers\DashboardController;
use Plugins\Admin\Controllers\EntitiesController;
use Plugins\Admin\Controllers\PluginsController;
use Plugins\Admin\Controllers\ThemeController;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;

/** @var Router $router */
$router->get('/assets/{file:.+}', [AssetController::class, 'serve']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->group('', static function (Router $router): void {
    $router->get('/', [DashboardController::class, 'index']);

    $router->get('/plugins', [PluginsController::class, 'index']);
    $router->post('/plugins/{slug}/toggle', [PluginsController::class, 'toggle']);

    $router->get('/themes', [ThemeController::class, 'index']);
    $router->post('/themes/upload', [ThemeController::class, 'upload']);
    $router->post('/themes/{slug}/activate', [ThemeController::class, 'activate']);

    $router->get('/entities/{entity_type}', [EntitiesController::class, 'list']);
    $router->get('/entities/{entity_type}/new', [EntitiesController::class, 'create']);
    $router->post('/entities/{entity_type}', [EntitiesController::class, 'store']);
    $router->get('/entities/{entity_type}/{id:\d+}', [EntitiesController::class, 'edit']);
    $router->post('/entities/{entity_type}/{id:\d+}', [EntitiesController::class, 'update']);
    $router->post('/entities/{entity_type}/{id:\d+}/delete', [EntitiesController::class, 'delete']);
}, [new AdminAuthMiddleware()]);
