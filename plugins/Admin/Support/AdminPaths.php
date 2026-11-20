<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

final class AdminPaths
{
    public static function slug(): string
    {
        $slug = strtolower(trim((string) ($_ENV['ADMIN_PATH'] ?? 'admin'), '/'));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'admin' : $slug;
    }

    public static function prefix(): string
    {
        return '/' . self::slug();
    }

    public static function path(string $sub = ''): string
    {
        if ($sub === '') {
            return self::prefix();
        }

        return self::prefix() . '/' . ltrim($sub, '/');
    }

    public static function translate(string $path): string
    {
        $slug = self::slug();
        if ($slug === 'admin') {
            return $path;
        }

        if ($path === '/admin') {
            return '/' . $slug;
        }

        if (strpos($path, '/admin/') === 0) {
            return '/' . $slug . substr($path, 6);
        }

        return $path;
    }
}
