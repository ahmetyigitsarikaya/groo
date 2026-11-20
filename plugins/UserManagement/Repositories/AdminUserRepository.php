<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Repositories;

use App\Core\Database\QueryBuilder;

/**
 * AdminUserRepository — admin_users tablosunu um_roles ile birlikte yönetir.
 *
 * Yetki hesaplama mantığı:
 *   1. Kullanıcıya um_roles atanmışsa → tüm rollerden gelen yetkiler birleştirilir.
 *   2. Hiç rol atanmamışsa VE admin_users.role = 'admin' → tam yetki (*).
 *   3. Hiç rol yok VE role başka bir değer → boş yetki.
 */
final class AdminUserRepository
{
    public const TABLE      = 'admin_users';
    public const PIVOT      = 'admin_user_roles';
    public const ROLES_TBL  = 'um_roles';

    // ----------------------------------------------------------------
    //  Sorgulama
    // ----------------------------------------------------------------

    public function all(): array
    {
        return QueryBuilder::table(self::TABLE)
            ->orderBy('id')
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

    public function count(): int
    {
        return QueryBuilder::table(self::TABLE)->count();
    }

    // ----------------------------------------------------------------
    //  Oluştur / Güncelle / Sil
    // ----------------------------------------------------------------

    public function create(string $name, string $email, string $password, string $role = 'editor'): int
    {
        return QueryBuilder::table(self::TABLE)->insert([
            'name'          => trim($name),
            'email'         => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => $role,
        ]);
    }

    public function update(int $id, array $data): bool
    {
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

    // ----------------------------------------------------------------
    //  Rol yönetimi
    // ----------------------------------------------------------------

    /** Admin kullanıcısının atanmış um_roles'larını döndürür. */
    public function roles(int $adminUserId): array
    {
        $pivotRows = QueryBuilder::table(self::PIVOT)
            ->select('role_id')
            ->where('admin_user_id', '=', $adminUserId)
            ->get();

        if ($pivotRows === []) {
            return [];
        }

        $ids = array_column($pivotRows, 'role_id');

        return QueryBuilder::table(self::ROLES_TBL)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    /** Rolleri tamamen değiştirir (sync). */
    public function syncRoles(int $adminUserId, array $roleIds): void
    {
        QueryBuilder::table(self::PIVOT)
            ->where('admin_user_id', '=', $adminUserId)
            ->delete();

        foreach ($roleIds as $rid) {
            QueryBuilder::table(self::PIVOT)->insert([
                'admin_user_id' => $adminUserId,
                'role_id'       => (int) $rid,
            ]);
        }
    }

    // ----------------------------------------------------------------
    //  Yetki hesaplama
    // ----------------------------------------------------------------

    /**
     * Admin kullanıcısının yetkilerini birleşik dizi olarak döndürür.
     *
     * Kural:
     *   - Rol varsa → rollerin JSON permission'ları birleştirilir.
     *   - Rol yok + legacy role='admin' → ['*'] (tam yetki sentineli).
     *   - Rol yok + diğer → [].
     */
    public function permissions(int $adminUserId): array
    {
        $roles = $this->roles($adminUserId);

        if ($roles === []) {
            $user = $this->find($adminUserId);
            if ($user !== null && ($user['role'] ?? '') === 'admin') {
                return ['*']; // tam yetki
            }
            return [];
        }

        $merged = [];
        foreach ($roles as $role) {
            $perms = json_decode((string) ($role['permissions'] ?? '[]'), true) ?? [];
            foreach ($perms as $p) {
                $merged[$p] = true;
            }
        }

        return array_keys($merged);
    }

    /**
     * Verilen admin kullanıcısının belirli bir yetkisi var mı?
     * '*' sentinel'i tam yetki anlamına gelir.
     */
    public function can(int $adminUserId, string $permission): bool
    {
        $perms = $this->permissions($adminUserId);
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}
