<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Plugins\Admin\Services\AdminAuthService;

final class PageCache
{
    private static function getCacheDir(): string
    {
        return GROO_BASE_PATH . '/storage/cache/html';
    }

    private static function getCacheFilePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $hash = md5($uri);
        return self::getCacheDir() . '/' . $hash . '.html';
    }

    public static function isCacheableRequest(): bool
    {
        // Yalnızca GET istekleri önbelleğe alınır
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $base = \App\Core\Config::get('app.base_path');
        if (!empty($base) && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . ltrim($uri, '/');

        // Admin, API, Install yollarını hariç tut
        $adminPath = $_ENV['ADMIN_PATH'] ?? 'admin';
        if (str_starts_with($uri, '/' . $adminPath) || str_starts_with($uri, '/api') || str_starts_with($uri, '/install')) {
            return false;
        }

        // Giriş yapmış (Admin) kullanıcıları hariç tut
        if (class_exists(AdminAuthService::class)) {
            $auth = new AdminAuthService();
            if ($auth->check() || $auth->checkCookieLogin()) {
                return false;
            }
        }

        return true;
    }

    public static function get(): ?string
    {
        $file = self::getCacheFilePath();
        if (file_exists($file)) {
            // İsteğe bağlı: Cache TTL eklenebilir, şimdilik kalıcı ve manuel flush ile silinir
            return file_get_contents($file) . "\n<!-- Served from HTML Cache -->";
        }
        return null;
    }

    public static function set(string $html): void
    {
        $dir = self::getCacheDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // 404 sayfası veya hata sayfasıysa cacheleme
        $status = http_response_code();
        if ($status !== false && $status >= 400) {
            return;
        }

        $file = self::getCacheFilePath();
        @file_put_contents($file, $html);
    }

    public static function flush(): void
    {
        $dir = self::getCacheDir();
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*.html');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
