<?php

declare(strict_types=1);

namespace App\Core\Template;

use App\Core\Database\Connection;
use App\Core\Events\EventDispatcher;
use PDO;
use ReflectionClass;

abstract class AbstractTemplate implements TemplateInterface
{
    protected array $metadata = [];

    public function getMetadata(): array
    {
        $file = $this->path('template.php');
        $fromFile = is_file($file) ? (array) require $file : [];

        return array_merge([
            'name' => (new ReflectionClass($this))->getShortName(),
            'slug' => strtolower((new ReflectionClass($this))->getShortName()),
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'requires' => [],
        ], $fromFile, $this->metadata);
    }

    public function getRequiredPlugins(): array
    {
        return array_values(array_map('strval', (array) ($this->getMetadata()['requires'] ?? [])));
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

    protected function viewsPath(): string
    {
        return $this->path('Views');
    }
}
