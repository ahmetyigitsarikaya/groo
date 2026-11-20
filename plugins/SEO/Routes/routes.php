<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\SEO\Controllers\Admin\SEOController;

/** @var Router $router */

$router->group('', static function (Router $router): void {
    
    $router->get('/', [SEOController::class, 'index']);
    
    $router->post('/meta', [SEOController::class, 'storeMeta']);
    $router->post('/meta/{id:\d+}/delete', [SEOController::class, 'deleteMeta']);
    
    $router->post('/settings', [SEOController::class, 'saveSettings']);
    $router->post('/robots', [SEOController::class, 'saveRobots']);
    
    $router->post('/clear-cache', [SEOController::class, 'clearCache']);

}, [new AdminAuthMiddleware('admin.seo')]);
