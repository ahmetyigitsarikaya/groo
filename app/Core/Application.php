<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database\MigrationManager;
use App\Core\Events\EventDispatcher;
use App\Core\Http\Router;
use App\Core\Plugin\PluginManager;
use App\Core\Template\TemplateManager;
use App\Core\View\View;
use Throwable;

final class Application
{
    private string $basePath;

    private Router $router;

    private PluginManager $plugins;

    private TemplateManager $templates;

    private EventDispatcher $events;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->events = EventDispatcher::instance();
    }

    public static function create(string $basePath): self
    {
        return new self($basePath);
    }

    public function boot(): void
    {
        date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

        $basePath = $this->resolveBasePath();
        $this->router = new Router($basePath);

        View::share('base_path', $basePath ?? '');

        $this->plugins = new PluginManager($this->basePath . DIRECTORY_SEPARATOR . 'plugins');
        $this->plugins->load();
        $this->plugins->registerAll();

        View::share('plugins', $this->plugins);

        $this->templates = new TemplateManager($this->basePath . DIRECTORY_SEPARATOR . 'templates');
        $this->templates->load($this->plugins);
        $this->templates->register();

        $this->runMigrations();
        $this->templates->runMigrations();

        $this->plugins->registerRoutes($this->router);
        $this->templates->registerRoutes($this->router);

        $this->plugins->bootAll();
        $this->templates->boot();

        $this->events->dispatch('app.booted', $this);
    }

    public function run(): void
    {
        // 1. PageCache: Cache'den servis edilebilir mi kontrol et
        $isCacheable = \App\Core\Middleware\PageCache::isCacheableRequest();
        if ($isCacheable) {
            $cachedOutput = \App\Core\Middleware\PageCache::get();
            if ($cachedOutput !== null) {
                echo $cachedOutput;
                return; // Router'ı çalıştırmadan direkt cache dön
            }
        }

        ob_start();
        $this->router->run();
        $html = ob_get_clean();

        // 2. HTML Optimizer (CLS & Lazy load & defer)
        if (\App\Core\Middleware\HTMLOptimizer::shouldOptimize()) {
            $html = \App\Core\Middleware\HTMLOptimizer::optimize($html);
        }

        // 3. PageCache: Çıktıyı cache'e yaz
        if ($isCacheable) {
            \App\Core\Middleware\PageCache::set($html);
        }

        echo $html;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getPlugins(): PluginManager
    {
        return $this->plugins;
    }

    public function getTemplates(): TemplateManager
    {
        return $this->templates;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    private function runMigrations(): void
    {
        if (!(bool) Config::get('app.auto_migrate', false)) {
            return;
        }

        try {
            $manager = new MigrationManager();
            $manager->run('core', $this->basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations');

            foreach ($this->plugins->getPlugins() as $slug => $plugin) {
                $path = $this->plugins->migrationsPathFor($slug);
                if ($path !== null) {
                    $manager->run($slug, $path);
                }
            }
        } catch (Throwable $e) {
            error_log('[Groo] Migration failed: ' . $e->getMessage());
        }
    }

    private function resolveBasePath(): ?string
    {
        $configured = Config::get('app.base_path');
        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -strlen('/public'));
        }

        return $scriptDir === '/' || $scriptDir === '\\' ? null : $scriptDir;
    }
}
