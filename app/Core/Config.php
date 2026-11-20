<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

final class Config
{
    private static array $items = [];

    private static bool $loaded = false;

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        $dotenv = Dotenv::createUnsafeMutable($basePath);
        $dotenv->safeLoad();

        self::$items['env'] = $_ENV;

        $configDir = $basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config';
        if (is_dir($configDir)) {
            foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                self::$items[basename($file, '.php')] = require $file;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $target = &self::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $target[$segment] = $value;
                return;
            }
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
    }

    public static function all(): array
    {
        return self::$items;
    }
}
