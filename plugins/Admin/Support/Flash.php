<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

final class Flash
{
    public static function set(string $type, string $message): void
    {
        Session::start();
        $_SESSION['_flash'][$type] = $message;
    }

    public static function pull(): array
    {
        Session::start();
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }

    public static function has(string $type): bool
    {
        Session::start();
        return isset($_SESSION['_flash'][$type]);
    }

    public static function get(string $type): ?string
    {
        Session::start();
        $message = $_SESSION['_flash'][$type] ?? null;
        if ($message !== null) {
            unset($_SESSION['_flash'][$type]);
        }
        return $message;
    }
}
