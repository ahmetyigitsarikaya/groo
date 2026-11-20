<?php

declare(strict_types=1);

namespace Plugins\ErrorPages;

use App\Core\Database\Connection;
use App\Core\Events\EventDispatcher;
use App\Core\Http\Router;
use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenu;

final class ErrorPagesPlugin extends AbstractPlugin
{
    public function register(): void
    {
        // Add Admin Menu Group inside System or standalone
        AdminMenu::add('Hata Sayfaları', '/admin/error-pages', 'icon-alert', 65, 'admin.error_pages', 'Sistem');
    }

    public function boot(): void
    {
        // Listen to core exceptions
        EventDispatcher::instance()->listen('core.exception.render', function (array $payload) {
            error_log('core.exception.render triggered with status: ' . ($payload['status'] ?? 'none'));
            $status = (int) ($payload['status'] ?? 500);
            
            // Check if there is an active custom page for this status
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare("SELECT * FROM error_pages WHERE error_code = ? AND is_active = 1");
            $stmt->execute([$status]);
            $page = $stmt->fetch();
            
            if ($page) {
                // Determine layout. If template has one, we can extend 'layouts/main' or fallback to a built-in one.
                // We will render a view from this plugin, which in turn extends the front-end layout.
                View::addPath(dirname(__DIR__) . '/ErrorPages/Views');
                
                // Clear any previous output
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

                http_response_code($status);
                echo View::make('error-page', ['page' => $page, 'status' => $status])->render();
                exit; // Stop execution, overriding the default Handler behavior
            }
        });
    }

    public function registerRoutes(Router $router): void
    {
        // Admin Routes
        $router->group('/admin/error-pages', function (Router $router) {
            $router->get('/', [\Plugins\ErrorPages\Controllers\Admin\ErrorPagesController::class, 'index']);
            $router->post('/save', [\Plugins\ErrorPages\Controllers\Admin\ErrorPagesController::class, 'save']);
        }, [new \Plugins\Admin\Http\Middleware\AdminAuthMiddleware()]);
    }
}
