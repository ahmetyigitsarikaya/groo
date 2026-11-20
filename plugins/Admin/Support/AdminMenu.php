<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

final class AdminMenu
{
    public static function add(string $title, string $route, string $icon = 'icon-dot', int $priority = 50): void
    {
        AdminMenuRegistry::instance()->add($title, $route, $icon, $priority);
    }

    public static function items(): array
    {
        return AdminMenuRegistry::instance()->items();
    }
}
