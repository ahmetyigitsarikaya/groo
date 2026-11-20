<?php

declare(strict_types=1);

namespace App\Core\Events;

final class EventDispatcher
{
    private static ?self $instance = null;

    private array $listeners = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
    }

    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    public function dispatch(string $event, $payload = null)
    {
        if (empty($this->listeners[$event])) {
            return $payload;
        }

        $listeners = $this->listeners[$event];
        krsort($listeners);

        $result = $payload;
        foreach ($listeners as $group) {
            foreach ($group as $listener) {
                $returned = $listener($result);
                if ($returned !== null) {
                    $result = $returned;
                }
            }
        }

        return $result;
    }
}
