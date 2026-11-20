<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Repositories;

use App\Core\Database\QueryBuilder;
use PDO;

/**
 * UserRepository — um_users tablosu için veri erişim katmanı.
 */
final class UserRepository
{
    public const TABLE = 'um_users';

    // ----------------------------------------------------------------
    //  Kullanıcı sorgulama
    // ----------------------------------------------------------------

    public function all(int $limit = 50, int $offset = 0): array
    {
        return QueryBuilder::table(self::TABLE)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    public function find(int $id): ?array
    {
        return QueryBuilder::table(self::TABLE)->find($id);
    }

    public function findByEmail(string $email): ?array
    {
        return QueryBuilder::table(self::TABLE)
            ->where('email', '=', strtolower(trim($email)))
            ->first();
    }

    public function findByUsername(string $username): ?array
    {
        return QueryBuilder::table(self::TABLE)
            ->where('username', '=', trim($username))
            ->first();
    }

    public function count(): int
    {
        return QueryBuilder::table(self::TABLE)->count();
    }

    public function countByStatus(string $status): int
    {
        return QueryBuilder::table(self::TABLE)
            ->where('status', '=', $status)
            ->count();
    }

    // ----------------------------------------------------------------
    //  Kullanıcı oluşturma / güncelleme / silme
    // ----------------------------------------------------------------

    public function create(string $username, string $email, string $password, string $status = 'active'): int
    {
        return QueryBuilder::table(self::TABLE)->insert([
            'username'      => trim($username),
            'email'         => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'status'        => $status,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        // Şifre değiştiriliyorsa hash'le
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        unset($data['password']);

        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->delete() > 0;
    }

    public function ban(int $id): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->update(['status' => 'banned']) > 0;
    }

    public function unban(int $id): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->update(['status' => 'active']) > 0;
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $user = $this->find($id);
        if ($user === null) {
            return false;
        }
        return password_verify($password, (string) $user['password_hash']);
    }

    // ----------------------------------------------------------------
    //  Roller
    // ----------------------------------------------------------------

    /** Kullanıcının rollerini döndürür. */
    public function roles(int $userId): array
    {
        $roleIds = QueryBuilder::table('um_user_roles')
            ->select('role_id')
            ->where('user_id', '=', $userId)
            ->get();

        if ($roleIds === []) {
            return [];
        }

        $ids = array_column($roleIds, 'role_id');

        return QueryBuilder::table('um_roles')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    /** Kullanıcıya rol ata (zaten varsa ignore). */
    public function assignRole(int $userId, int $roleId): void
    {
        $exists = QueryBuilder::table('um_user_roles')
            ->where('user_id', '=', $userId)
            ->where('role_id', '=', $roleId)
            ->count();

        if ($exists === 0) {
            QueryBuilder::table('um_user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }

    /** Kullanıcıdan rol kaldır. */
    public function removeRole(int $userId, int $roleId): void
    {
        QueryBuilder::table('um_user_roles')
            ->where('user_id', '=', $userId)
            ->where('role_id', '=', $roleId)
            ->delete();
    }

    /** Kullanıcının rollerini tamamen değiştir. */
    public function syncRoles(int $userId, array $roleIds): void
    {
        QueryBuilder::table('um_user_roles')
            ->where('user_id', '=', $userId)
            ->delete();

        foreach ($roleIds as $roleId) {
            QueryBuilder::table('um_user_roles')->insert([
                'user_id' => $userId,
                'role_id' => (int) $roleId,
            ]);
        }
    }

    /** Kullanıcının belirli bir yetkisi var mı? */
    public function hasPermission(int $userId, string $permission): bool
    {
        $roles = $this->roles($userId);
        foreach ($roles as $role) {
            $perms = json_decode((string) ($role['permissions'] ?? '[]'), true) ?? [];
            if (in_array($permission, $perms, true)) {
                return true;
            }
        }
        return false;
    }
}
