<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Repositories;

use App\Core\Database\QueryBuilder;

/**
 * ApiKeyRepository — um_api_keys tablosu için veri erişim katmanı.
 */
final class ApiKeyRepository
{
    public const TABLE = 'um_api_keys';

    public function forUser(int $userId): array
    {
        return QueryBuilder::table(self::TABLE)
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function find(int $id): ?array
    {
        return QueryBuilder::table(self::TABLE)->find($id);
    }

    public function findByKey(string $apiKey): ?array
    {
        return QueryBuilder::table(self::TABLE)
            ->where('api_key', '=', $apiKey)
            ->where('is_active', '=', 1)
            ->first();
    }

    /** Yeni API anahtarı üretir ve kaydeder; üretilen key'i döndürür. */
    public function generate(int $userId, string $label = ''): string
    {
        $key = 'groo_' . bin2hex(random_bytes(24)); // 53 karakter

        QueryBuilder::table(self::TABLE)->insert([
            'user_id'  => $userId,
            'api_key'  => $key,
            'label'    => $label,
            'is_active' => 1,
        ]);

        return $key;
    }

    public function revoke(int $id, int $userId): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->where('user_id', '=', $userId)
            ->update(['is_active' => 0]) > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        return QueryBuilder::table(self::TABLE)
            ->where('id', '=', $id)
            ->where('user_id', '=', $userId)
            ->delete() > 0;
    }

    /** Son kullanım zamanını günceller (API isteği başına). */
    public function touchLastUsed(string $apiKey): void
    {
        QueryBuilder::table(self::TABLE)
            ->where('api_key', '=', $apiKey)
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);
    }
}
