<?php

declare(strict_types=1);

namespace Plugins\SEO\Helpers;

use App\Core\Config;

final class Cache
{
    private static function getCacheDir(): string
    {
        $dir = dirname(__DIR__, 3) . '/storage/cache/seo';
        
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
        return $dir;
    }

    public static function get(string $key)
    {
        $file = self::getCacheDir() . '/' . md5($key) . '.php';
        
        if (is_file($file)) {
            $data = include $file;
            if (is_array($data) && isset($data['expires_at'], $data['value'])) {
                if ($data['expires_at'] === 0 || $data['expires_at'] > time()) {
                    return $data['value'];
                }
                unlink($file); // Expired
            }
        }
        return null;
    }

    public static function set(string $key, $value, int $ttlSeconds = 3600): void
    {
        $file = self::getCacheDir() . '/' . md5($key) . '.php';
        $expiresAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        
        $content = "<?php\nreturn " . var_export([
            'expires_at' => $expiresAt,
            'value' => $value
        ], true) . ";\n";
        
        file_put_contents($file, $content, LOCK_EX);
    }

    public static function clear(): void
    {
        $dir = self::getCacheDir();
        $files = glob($dir . '/*.php');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
