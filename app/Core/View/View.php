<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;
use App\Core\Database\DynamicEntityRepository;
use App\Core\Plugin\PluginManager;
use RuntimeException;
use Throwable;

final class View
{
    private static string $basePath = '';

    private static array $extraPaths = [];

    private static array $shared = [];

    private string $template;

    private array $data;

    private ?string $layout = null;

    private array $sections = [];

    private array $sectionStack = [];

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = array_merge(self::$shared, $data);
    }

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/\\');
    }

    public static function getBasePath(): string
    {
        return self::$basePath;
    }

    public static function addPath(string $path): void
    {
        $path = rtrim($path, '/\\');
        if ($path !== '' && !in_array($path, self::$extraPaths, true)) {
            array_unshift(self::$extraPaths, $path);
        }
    }

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(string $key, $default = null)
    {
        return self::$shared[$key] ?? $default;
    }

    public static function make(string $template, array $data = []): self
    {
        return new self($template, $data);
    }

    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function render(): string
    {
        $content = $this->capture($this->template);

        if ($this->layout !== null) {
            $this->sections['__content'] = $content;
            $content = $this->capture($this->layout);
        }

        return $content;
    }

    public function display(): void
    {
        echo $this->render();
    }

    protected function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    protected function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    protected function endSection(): void
    {
        if ($this->sectionStack === []) {
            throw new RuntimeException('View section stack is empty.');
        }

        $name = array_pop($this->sectionStack);
        $this->sections[$name] = (string) ob_get_clean();
    }

    protected function yieldSection(string $name, string $default = ''): void
    {
        echo $this->sections[$name] ?? $default;
    }

    protected function sectionContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    protected function partial(string $template, array $data = []): void
    {
        echo $this->capture($template, $data);
    }

    protected function component(string $name, array $data = []): void
    {
        $this->partial('components/' . $name, $data);
    }

    protected function data(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    protected function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    protected function route(string $path = '/'): string
    {
        $base = (string) ($this->data['base_path'] ?? Config::get('app.base_path') ?? '');
        $base = rtrim($base, '/');
        $path = '/' . ltrim($path, '/');
        $path = self::translateAdminAlias($path);

        $url = $base . $path;

        return $url === '' ? '/' : $url;
    }

    protected function asset(string $path): string
    {
        return $this->route('/assets/' . ltrim($path, '/'));
    }

    protected function media(string $path): string
    {
        return $this->route('/media/view/' . ltrim($path, '/'));
    }

    protected function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }

    protected function plugins(): ?PluginManager
    {
        $plugins = $this->data['plugins'] ?? null;

        return $plugins instanceof PluginManager ? $plugins : null;
    }

    protected function records(string $entitySlug): array
    {
        try {
            return (new DynamicEntityRepository())->allRecords($entitySlug);
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function translateAdminAlias(string $path): string
    {
        $slug = trim((string) ($_ENV['ADMIN_PATH'] ?? ''), '/');
        if ($slug === '' || $slug === 'admin') {
            return $path;
        }

        if ($path === '/admin') {
            return '/' . $slug;
        }

        if (strpos($path, '/admin/') === 0) {
            return '/' . $slug . substr($path, 6);
        }

        return $path;
    }

    private function capture(string $template, array $extra = []): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';
        $file = $this->resolveFile($relative);

        if ($file === null) {
            throw new RuntimeException(sprintf('View "%s" not found.', $template));
        }

        extract(array_merge($this->data, $extra), EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    private function resolveFile(string $relative): ?string
    {
        foreach (array_merge(self::$extraPaths, [self::$basePath]) as $base) {
            if ($base === '') {
                continue;
            }
            $candidate = $base . DIRECTORY_SEPARATOR . $relative;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
