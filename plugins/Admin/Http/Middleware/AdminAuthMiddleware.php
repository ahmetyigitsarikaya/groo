<?php
declare(strict_types=1);
namespace Plugins\Admin\Http\Middleware;
use App\Core\Config;
use Plugins\Admin\Services\AdminAuthService;

final class AdminAuthMiddleware
{
    private AdminAuthService $auth;
    private ?string $permission;

    public function __construct($authOrPermission = null, ?AdminAuthService $auth = null)
    {
        if ($authOrPermission instanceof AdminAuthService) {
            $this->auth       = $authOrPermission;
            $this->permission = null;
        } elseif (is_string($authOrPermission)) {
            $this->auth       = $auth ?? new AdminAuthService();
            $this->permission = $authOrPermission;
        } else {
            $this->auth       = new AdminAuthService();
            $this->permission = null;
        }
    }

    public function __invoke(array $params, callable $next)
    {
        $base = rtrim((string)(Config::get("app.base_path") ?? ""), "/");
        if (!$this->auth->check() && !$this->auth->checkCookieLogin()) {
            header("Location: " . $base . \Plugins\Admin\Support\AdminPaths::path('/login'), true, 302);
            exit;
        }
        if ($this->permission !== null && !$this->auth->can($this->permission) && !$this->auth->can("*")) {
            header("Location: " . $base . \Plugins\Admin\Support\AdminPaths::path('/'), true, 302);
            exit;
        }
        return $next($params);
    }
}
