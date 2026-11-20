<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Controllers;

use App\Core\Config;
use App\Core\View\View;
use Plugins\Admin\Services\AdminAuthService;
use Plugins\Admin\Support\AdminMenuRegistry;
use Plugins\Admin\Support\AdminPaths;
use Plugins\Admin\Support\Csrf;
use Plugins\Admin\Support\Flash;

/**
 * UserManagement eklentisinin controller tabanı.
 * Admin plugin'in BaseController'ını kopyalamak yerine,
 * aynı AdminAuthService ve View altyapısını kullanır.
 */
abstract class BaseController
{
    protected AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    protected function render(string $template, array $data = []): void
    {
        $allMenu      = AdminMenuRegistry::instance()->items();
        $filteredMenu = array_values(array_filter(
            $allMenu,
            function (array $item): bool {
                $perm = (string) ($item['permission'] ?? '');
                return $perm === '' || $this->auth->can($perm);
            }
        ));

        View::make($template, array_merge([
            'admin_user'        => $this->auth->user(),
            'admin_permissions' => $this->auth->permissions(),
            'menu'              => $filteredMenu,
            'flash'             => Flash::pull(),
            'csrf_field'        => Csrf::field(),
        ], $data))->display();
    }

    protected function redirect(string $path): void
    {
        $base = rtrim((string) (Config::get('app.base_path') ?? ''), '/');
        header('Location: ' . $base . AdminPaths::translate($path), true, 303);
        exit;
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('error', 'Güvenlik anahtarı (CSRF) doğrulanamadı. Lütfen tekrar deneyin.');
            $this->redirect('/admin/users');
        }
    }

    protected function requireAuth(): void
    {
        if (!$this->auth->check()) {
            $base = rtrim((string) (Config::get('app.base_path') ?? ''), '/');
            header('Location: ' . $base . AdminPaths::path('/login'), true, 302);
            exit;
        }
    }

    /** POST girdisini string olarak alır. */
    protected function input(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    /** POST girdisini integer olarak alır. */
    protected function inputInt(string $key, int $default = 0): int
    {
        return (int) ($_POST[$key] ?? $default);
    }
}
