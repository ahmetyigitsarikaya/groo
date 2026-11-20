<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

use App\Core\Events\EventDispatcher;

final class AdminMenuRegistry
{
    private static ?self $instance = null;

    private array $items = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function add(string $title, string $route, string $icon = 'icon-dot', int $priority = 50): void
    {
        foreach ($this->items as &$item) {
            if ($item['route'] === $route) {
                $item['title'] = $title;
                $item['icon'] = $icon;
                $item['priority'] = $priority;
                return;
            }
        }
        unset($item);

        $this->items[] = [
            'title' => $title,
            'route' => $route,
            'icon' => $icon,
            'priority' => $priority,
        ];

        EventDispatcher::instance()->dispatch(AdminMenuEvent::ADDED, [
            'title' => $title,
            'route' => $route,
            'icon' => $icon,
        ]);
    }

    public function items(): array
    {
        $items = $this->items;

        usort($items, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        return $items;
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
