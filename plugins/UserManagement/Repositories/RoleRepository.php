<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Repositories;

use App\Core\Database\QueryBuilder;

/**
 * RoleRepository — um_roles tablosu için veri erişim katmanı.
 */
final class RoleRepository
{
    public const TABLE = 'um_roles';

    public function all(): array
    {
        return QueryBuilder::table(self::TABLE)
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?array
    {
        return QueryBuilder::table(self::TABLE)->find($id);
    }

    public function findByName(string $name): ?array
    {
        return QueryBuilder::table(self::TABLE)
            ->where('name', '=', $name)
            ->first();
    }

    public function create(string $name, string $color, array $permissions): int
    {
        return QueryBuilder::table(self::TABLE)->insert([
            'name'        => trim($name),
            'color'       => $this->sanitizeColor($color),
            'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function update(int $id, string $name, string $color, array $permissions): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->update([
                'name'        => trim($name),
                'color'       => $this->sanitizeColor($color),
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            ]) >= 0;
    }

    public function delete(int $id): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->delete() > 0;
    }

    /** Rolün yetkilerini dizi olarak döndürür. */
    public function permissions(int $id): array
    {
        $role = $this->find($id);
        if ($role === null) {
            return [];
        }
        return json_decode((string) ($role['permissions'] ?? '[]'), true) ?? [];
    }

    /** Tüm tanımlı yetki düğümlerini döndürür (statik liste). */
    public static function availablePermissions(): array
    {
        return [
            'admin'   => [
                'admin.dashboard' => 'Admin paneline erişim (zorunlu)',
                'admin.users'     => 'Kullanıcı yönetimi',
                'admin.roles'     => 'Rol yönetimi',
                'admin.manage'    => 'Admin hesaplarını yönet',
                'admin.plugins'   => 'Eklenti yönetimi',
                'admin.themes'    => 'Tema yönetimi',
                'admin.entities'  => 'Entity yönetimi',
                'admin.traffic'   => 'Trafik analizlerini görüntüle',
                'admin.seo'       => 'SEO motorunu yönet',
            ],
            'users'   => [
                'users.view'   => 'Kullanıcıları görüntüle',
                'users.create' => 'Kullanıcı oluştur',
                'users.edit'   => 'Kullanıcı düzenle',
                'users.ban'    => 'Kullanıcı yasakla',
                'users.delete' => 'Kullanıcı sil',
            ],
            'roles'   => [
                'roles.manage' => 'Rolleri yönet',
            ],
            'content' => [
                'content.view'   => 'İçeriği görüntüle',
                'content.edit'   => 'İçerik düzenle',
                'content.delete' => 'İçerik sil',
            ],
            'api'     => [
                'api.manage' => 'API anahtarlarını yönet',
            ],
        ];
    }

    /** HEX renk kodunu doğrula ve düzelt. */
    private function sanitizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtolower($color);
        }
        return '#99aab5'; // varsayılan Discord gri
    }
}
