<?php
declare(strict_types=1);
namespace Plugins\Admin\Services;
use App\Core\Database\QueryBuilder;
use Plugins\Admin\Support\Session;

final class AdminAuthService
{
    public const TABLE = "um_users";

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        if (!$this->checkRateLimit($email)) {
            throw new \Exception("Çok fazla hatalı giriş denemesi. Lütfen 15 dakika bekleyin.");
        }

        $user = QueryBuilder::table(self::TABLE)
            ->where("email", "=", strtolower(trim($email)))
            ->first();
            
        if ($user === null) {
            password_verify($password, "\$2y\$10\$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvo");
            $this->recordFailedAttempt($email);
            return false;
        }
        
        if ((string)($user["status"] ?? "active") !== "active") {
            $this->recordFailedAttempt($email);
            return false;
        }
        
        if (!password_verify($password, (string)$user["password_hash"])) {
            $this->recordFailedAttempt($email);
            return false;
        }
        
        $this->clearFailedAttempts($email);
        $this->login($user, $remember);
        return true;
    }

    private function checkRateLimit(string $email): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $attempt = QueryBuilder::table("um_login_attempts")
            ->where("ip_address", "=", $ip)
            ->where("email", "=", strtolower(trim($email)))
            ->first();

        if ($attempt !== null) {
            if ($attempt['locked_until'] !== null && strtotime($attempt['locked_until']) > time()) {
                return false;
            }
        }
        return true;
    }

    private function recordFailedAttempt(string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $pdo = \App\Core\Database\Connection::getInstance();
        $email = strtolower(trim($email));
        
        $attempt = QueryBuilder::table("um_login_attempts")
            ->where("ip_address", "=", $ip)
            ->where("email", "=", $email)
            ->first();

        if ($attempt === null) {
            QueryBuilder::table("um_login_attempts")->insert([
                "ip_address" => $ip,
                "email" => $email,
                "attempts" => 1,
            ]);
        } else {
            $attempts = (int)$attempt['attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + (15 * 60)); // Lock for 15 minutes
            }
            QueryBuilder::table("um_login_attempts")
                ->where("id", "=", (int)$attempt['id'])
                ->update([
                    "attempts" => $attempts,
                    "locked_until" => $lockedUntil,
                    "updated_at" => date('Y-m-d H:i:s')
                ]);
        }
    }

    private function clearFailedAttempts(string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        QueryBuilder::table("um_login_attempts")
            ->where("ip_address", "=", $ip)
            ->where("email", "=", strtolower(trim($email)))
            ->delete();
    }

    public function login(array $user, bool $remember = false): void
    {
        Session::start();
        session_regenerate_id(true);
        $name = (string)($user["name"] ?? $user["username"] ?? $user["email"] ?? "Admin");
        $permissions = $this->resolvePermissions((int)$user["id"]);
        $roleName = "Yonetici";
        if (in_array("*", $permissions, true)) $roleName = "Super Admin";
        
        $_SESSION["admin_user"] = [
            "id"          => (int)$user["id"],
            "name"        => $name,
            "email"       => (string)$user["email"],
            "role"        => $roleName,
            "permissions" => $permissions,
        ];

        if ($remember) {
            $this->setRememberCookie((int)$user["id"]);
        }
    }

    private function setRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $pdo = \App\Core\Database\Connection::getInstance();
        $pdo->prepare("UPDATE um_users SET remember_token = ? WHERE id = ?")
            ->execute([$token, $userId]);
        
        setcookie("admin_remember", $token, [
            'expires' => time() + (30 * 24 * 60 * 60), // 30 days
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public function logout(): void 
    { 
        Session::start();
        $user = $this->user();
        if ($user !== null) {
            $pdo = \App\Core\Database\Connection::getInstance();
            $pdo->prepare("UPDATE um_users SET remember_token = NULL WHERE id = ?")
                ->execute([(int)$user['id']]);
        }
        setcookie("admin_remember", "", time() - 3600, "/");
        Session::destroy(); 
    }

    public function checkCookieLogin(): bool
    {
        if (isset($_COOKIE['admin_remember']) && !$this->check()) {
            $token = $_COOKIE['admin_remember'];
            $pdo = \App\Core\Database\Connection::getInstance();
            $stmt = $pdo->prepare("SELECT * FROM um_users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && ((string)($user["status"] ?? "active") === "active")) {
                $this->login($user, false);
                return true;
            } else {
                setcookie("admin_remember", "", time() - 3600, "/");
            }
        }
        return false;
    }

    public function check(): bool { return $this->user() !== null; }

    public function user(): ?array
    {
        Session::start();
        $user = $_SESSION["admin_user"] ?? null;
        return is_array($user) ? $user : null;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user !== null ? (int)$user["id"] : null;
    }

    public function can(string $permission): bool
    {
        $user = $this->user();
        if ($user === null) return false;
        $permissions = (array)($user["permissions"] ?? []);
        return in_array("*", $permissions, true) || in_array($permission, $permissions, true);
    }

    public function permissions(): array
    {
        $user = $this->user();
        return $user !== null ? (array)($user["permissions"] ?? []) : [];
    }

    private function resolvePermissions(int $userId): array
    {
        try {
            $pivotRows = QueryBuilder::table("um_user_roles")->where("user_id", "=", $userId)->get();
            if (empty($pivotRows)) return ["*"];
            foreach ($pivotRows as $row) {
                $role = QueryBuilder::table("um_roles")->find((int)$row["role_id"]);
                if ($role === null) continue;
                $roleName = strtolower((string)($role["name"] ?? ""));
                if ($roleName === "super admin" || $roleName === "superadmin") return ["*"];
            }
            return ["admin"];
        } catch (\Throwable $e) {
            return ["*"];
        }
    }
}
