<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\Config;
use App\Core\View\View;
use Plugins\Admin\Services\AdminAuthService;
use Plugins\Admin\Support\AdminMenuRegistry;
use Plugins\Admin\Support\Csrf;
use Plugins\Admin\Support\Flash;

abstract class BaseController
{
    protected AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    protected function render(string $template, array $data = []): void
    {
        View::make($template, array_merge([
            'admin_user' => $this->auth->user(),
            'menu' => AdminMenuRegistry::instance()->items(),
            'flash' => Flash::pull(),
            'csrf_field' => Csrf::field(),
        ], $data))->display();
    }

    protected function redirect(string $path): void
    {
        $base = rtrim((string) (Config::get('app.base_path') ?? ''), '/');
        $translatedPath = \Plugins\Admin\Support\AdminPaths::translate($path);
        header('Location: ' . $base . $translatedPath, true, 303);
        exit;
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Guvenlik anahtari (CSRF) dogrulanamadi. Lutfen tekrar deneyin.');
            $this->redirect('/admin');
        }
    }
}
