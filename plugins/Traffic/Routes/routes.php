<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\Traffic\Controllers\TrafficController;

/** @var Router $router */

$router->group('', static function (Router $router): void {
    $router->get('/', [TrafficController::class, 'index']);
}, [new AdminAuthMiddleware('admin.traffic')]);
