<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

final class Csrf
{
    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function validate(?string $token): bool
    {
        Session::start();

        return is_string($token) && $token !== '' && hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token);
    }
}
