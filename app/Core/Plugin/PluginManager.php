<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use App\Core\Config;
use App\Core\Exceptions\PluginException;
use App\Core\Http\Router;

final class PluginManager
{
    private string $pluginsPath;

    /** @var array<string, PluginInterface> */
    private array $plugins = [];

    /** @var array<string, array> */
    private array $metadata = [];

    public function __construct(string $pluginsPath)
    {
        $this->pluginsPath = rtrim($pluginsPath, '/\\');
    }

    public function load(): void
    {
        $discovered = $this->discover();
        $active = Config::get('plugins.active');

        if (is_array($active)) {
            $discovered = array_intersect_key($discovered, array_flip(array_map('strtolower', $active)));
        }

        $ordered = $this->resolveDependencies($discovered);

        foreach ($ordered as $slug => $class) {
            $plugin = new $class();
            if (!$plugin instanceof PluginInterface) {
                throw new PluginException(sprintf('Plugin "%s" must implement PluginInterface.', $class));
            }
            $this->plugins[$slug] = $plugin;
            $this->metadata[$slug] = $plugin->getMetadata();
        }
    }

    public function registerAll(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->register();
        }
    }

    public function bootAll(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }
    }

    public function registerRoutes(Router $router): void
    {
        foreach ($this->plugins as $slug => $plugin) {
            // 1. If the plugin overrides registerRoutes(), call it directly
            if (method_exists($plugin, 'registerRoutes')) {
                $plugin->registerRoutes($router);
            }

            // 2. Also load Routes/routes.php if it exists
            $meta       = $this->metadata[$slug];
            $routesFile = $this->pluginsPath . DIRECTORY_SEPARATOR . $this->dirOf($slug) . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'routes.php';

            if (!is_file($routesFile)) {
                continue;
            }

            $prefix = array_key_exists('route_prefix', $meta)
                ? (string) $meta['route_prefix']
                : '/' . $slug;

            $router->group($prefix, static function (Router $router) use ($routesFile): void {
                require $routesFile;
            });
        }
    }


    /** @return array<string, PluginInterface> */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getPlugin(string $slug): ?PluginInterface
    {
        return $this->plugins[strtolower($slug)] ?? null;
    }

    public function getMetadataFor(string $slug): ?array
    {
        return $this->metadata[strtolower($slug)] ?? null;
    }

    public function migrationsPathFor(string $slug): ?string
    {
        $dir = $this->pluginsPath . DIRECTORY_SEPARATOR . $this->dirOf($slug) . DIRECTORY_SEPARATOR . 'Migrations';

        return is_dir($dir) ? $dir : null;
    }

    private function dirOf(string $slug): string
    {
        foreach (scandir($this->pluginsPath) ?: [] as $entry) {
            if (strtolower($entry) === $slug) {
                return $entry;
            }
        }

        return $slug;
    }

    /** @return array<string, class-string> */
    private function discover(): array
    {
        $found = [];

        foreach (scandir($this->pluginsPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $dir = $this->pluginsPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($dir)) {
                continue;
            }

            $classFile = $dir . DIRECTORY_SEPARATOR . $entry . 'Plugin.php';
            if (!is_file($classFile)) {
                continue;
            }

            $class = 'Plugins\\' . $entry . '\\' . $entry . 'Plugin';
            if (!class_exists($class)) {
                require_once $classFile;
            }

            if (!class_exists($class)) {
                throw new PluginException(sprintf('Plugin class "%s" not found in "%s".', $class, $classFile));
            }

            $found[strtolower($entry)] = $class;
        }

        return $found;
    }

    /**
     * @param array<string, class-string> $plugins
     * @return array<string, class-string>
     */
    private function resolveDependencies(array $plugins): array
    {
        $resolved = [];
        $visiting = [];

        $visit = function (string $slug) use (&$visit, &$resolved, &$visiting, $plugins): void {
            if (isset($resolved[$slug])) {
                return;
            }
            if (isset($visiting[$slug])) {
                throw new PluginException(sprintf('Circular plugin dependency detected at "%s".', $slug));
            }
            if (!isset($plugins[$slug])) {
                throw new PluginException(sprintf('Required plugin "%s" is missing or inactive.', $slug));
            }

            $visiting[$slug] = true;

            $class = $plugins[$slug];
            $metadata = (new $class())->getMetadata();
            foreach ((array) ($metadata['requires'] ?? []) as $requirement) {
                $visit(strtolower((string) $requirement));
            }

            unset($visiting[$slug]);
            $resolved[$slug] = $class;
        };

        foreach (array_keys($plugins) as $slug) {
            $visit($slug);
        }

        return $resolved;
    }
}
