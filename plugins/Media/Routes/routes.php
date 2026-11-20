<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\Media\Controllers\MediaController;

/** @var Router $router */

$router->group('', static function (Router $router): void {
    $router->get('/', [MediaController::class, 'index']);
    $router->get('/list', [MediaController::class, 'list']);
    $router->post('/upload', [MediaController::class, 'upload']);
    $router->post('/delete', [MediaController::class, 'delete']);
    $router->post('/optimize-theme-assets', [MediaController::class, 'optimizeThemeAssets']);
}, [new AdminAuthMiddleware('admin')]);
