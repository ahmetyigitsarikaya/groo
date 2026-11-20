<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use App\Core\Database\Connection;
use App\Core\Events\EventDispatcher;
use PDO;
use ReflectionClass;

abstract class AbstractPlugin implements PluginInterface
{
    protected array $metadata = [];

    public function getMetadata(): array
    {
        return array_merge([
            'name' => (new ReflectionClass($this))->getShortName(),
            'slug' => strtolower((new ReflectionClass($this))->getShortName()),
            'version' => '1.0.0',
            'description' => '',
            'requires' => [],
            'route_prefix' => '',
        ], $this->metadata);
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }

    protected function events(): EventDispatcher
    {
        return EventDispatcher::instance();
    }

    protected function db(): PDO
    {
        return Connection::getInstance();
    }

    protected function path(string $relative = ''): string
    {
        $base = dirname((new ReflectionClass($this))->getFileName());

        return $relative === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($relative, '/\\');
    }
}
