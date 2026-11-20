<?php

declare(strict_types=1);

namespace App\Core\Template;

use App\Core\Config;
use App\Core\Database\MigrationManager;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\TemplateException;
use App\Core\Http\Router;
use App\Core\Plugin\PluginManager;
use App\Core\View\View;

final class TemplateManager
{
    private const ASSET_MIME_TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain; charset=utf-8',
    ];

    private string $templatesPath;

    private ?TemplateInterface $template = null;

    private array $metadata = [];

    private string $activeDir = '';

    public function __construct(string $templatesPath)
    {
        $this->templatesPath = rtrim($templatesPath, '/\\');
    }

    public function load(PluginManager $plugins): void
    {
        $active = Config::get('templates.active');
        if (!is_string($active) || trim($active) === '') {
            return;
        }

        $active = trim($active);
        $dir = $this->findDirectory($active);
        if ($dir === null) {
            throw new TemplateException(sprintf('Active template "%s" was not found in the templates directory.', $active));
        }

        $path = $this->templatesPath . DIRECTORY_SEPARATOR . $dir;
        $metaFile = $path . DIRECTORY_SEPARATOR . 'template.php';
        $metadata = is_file($metaFile) ? (array) require $metaFile : [];

        foreach ((array) ($metadata['requires'] ?? []) as $required) {
            if ($plugins->getPlugin((string) $required) === null) {
                throw new TemplateException(sprintf('Template "%s" requires the plugin "%s" to be active.', $active, $required));
            }
        }

        // Linux'ta dosya sistemi büyük/küçük harf duyarlıdır.
        // ucfirst(strtolower()) ile türetilen isim (örn. DualTesisat → Dualtesisat)
        // gerçek dosya adıyla eşleşmeyebilir. Klasörü tarayarak gerçek dosyayı buluyoruz.
        $classFile = null;
        $studly    = null;
        foreach (scandir($path) ?: [] as $entry) {
            if (preg_match('/^(.+)Template\.php$/i', $entry, $m) && is_file($path . DIRECTORY_SEPARATOR . $entry)) {
                $classFile = $path . DIRECTORY_SEPARATOR . $entry;
                $studly    = $m[1]; // Gerçek PascalCase ad: örn. "DualTesisat"
                break;
            }
        }

        if ($classFile === null || $studly === null) {
            throw new TemplateException(sprintf(
                'Template class file "*Template.php" was not found in "%s".', $path
            ));
        }

        $class = 'Templates\\' . $studly . '\\' . $studly . 'Template';
        if (!class_exists($class)) {
            require_once $classFile;
        }

        if (!class_exists($class)) {
            throw new TemplateException(sprintf('Template class "%s" could not be loaded.', $class));
        }

        $template = new $class();
        if (!$template instanceof TemplateInterface) {
            throw new TemplateException(sprintf('Template "%s" must implement TemplateInterface.', $class));
        }

        $this->template = $template;
        $this->metadata = $template->getMetadata();
        $this->activeDir = $dir;

        $viewsPath = $path . DIRECTORY_SEPARATOR . 'Views';
        if (is_dir($viewsPath)) {
            View::setBasePath($viewsPath);
        }

        View::share('plugins', $plugins);
        View::share('template', $this->metadata);
    }

    public function register(): void
    {
        if ($this->template !== null) {
            $this->template->register();
        }
    }

    public function boot(): void
    {
        if ($this->template !== null) {
            $this->template->boot();
        }
    }

    public function runMigrations(): void
    {
        if ($this->template === null || !(bool) Config::get('app.auto_migrate', false)) {
            return;
        }

        $path = $this->activePath() . DIRECTORY_SEPARATOR . 'Migrations';
        if (!is_dir($path)) {
            return;
        }

        (new MigrationManager())->run('tpl_' . strtolower($this->activeDir), $path);
    }

    public function registerRoutes(Router $router): void
    {
        if ($this->template === null) {
            return;
        }

        $router->get('/assets/{file:.+}', function (array $params): void {
            $this->serveAsset((string) ($params['file'] ?? ''));
        });

        // Yeni Görsel Servis API'si (Medya havuzundan çekmek için)
        $router->get('/media/view/{file:.+}', function (array $params): void {
            $this->serveMediaAsset((string) ($params['file'] ?? ''));
        });

        $routesFile = $this->activePath() . DIRECTORY_SEPARATOR . 'routes.php';
        if (is_file($routesFile)) {
            $router->group('', static function (Router $router) use ($routesFile): void {
                require $routesFile;
            });
        }
    }

    public function getActive(): ?TemplateInterface
    {
        return $this->template;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->template !== null;
    }

    private function checkHotlinkProtection(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $hostParts = explode(':', $host);
            $cleanHost = $hostParts[0];

            if ($refererHost !== null && $refererHost !== $cleanHost) {
                // Hotlink detected
                header('HTTP/1.1 403 Forbidden');
                exit('Hotlinking is not allowed.');
            }
        }
    }

    private function serveAsset(string $file): void
    {
        $this->checkHotlinkProtection();

        $root = realpath($this->activePath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets');
        if ($root === false) {
            throw new NotFoundException('Template assets are not available.');
        }

        $clean = str_replace(['..', "\0"], '', ltrim(str_replace('\\', '/', urldecode($file)), '/'));
        $target = realpath($root . DIRECTORY_SEPARATOR . $clean);

        $baseDir = defined('GROO_BASE_PATH') ? rtrim(GROO_BASE_PATH, '/\\') : rtrim(dirname(__DIR__, 2), '/\\');
        $themeUploadDir = realpath($baseDir . '/public/uploads/themes/' . $this->activeDir);

        // Dosya normal yolda yoksa, uploads dizinine bak (örneğin WebP versiyonu)
        if ($target === false || strpos($target, $root) !== 0 || !is_file($target)) {
            $altTarget = $themeUploadDir ? realpath($themeUploadDir . DIRECTORY_SEPARATOR . $clean) : false;
            if ($altTarget !== false && strpos($altTarget, $themeUploadDir) === 0 && is_file($altTarget)) {
                $target = $altTarget;
            } else {
                throw new NotFoundException('Asset not found.');
            }
        }

        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));

        // Eğer .jpg / .png ise yine uploads dizininde .webp varyasyonunu kontrol et
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $filename = pathinfo($target, PATHINFO_FILENAME);
            
            if (is_dir($themeUploadDir)) {
                $matches = glob($themeUploadDir . '/' . $filename . '_*.webp');
                if (!empty($matches) && is_file($matches[0])) {
                    $target = $matches[0];
                    $extension = 'webp';
                }
            }

            if ($extension !== 'webp' && is_file($target . '.webp')) {
                $target = $target . '.webp';
                $extension = 'webp';
            }
        }

        $this->sendFile($target, $extension);
    }

    private function serveMediaAsset(string $file): void
    {
        $this->checkHotlinkProtection();

        $baseDir = defined('GROO_BASE_PATH') ? rtrim(GROO_BASE_PATH, '/\\') : rtrim(dirname(__DIR__, 2), '/\\');
        $root = realpath($baseDir . '/public/uploads/media');
        
        if ($root === false) {
            throw new NotFoundException('Media directory is not available.');
        }

        $clean = str_replace(['..', "\0"], '', ltrim(str_replace('\\', '/', urldecode($file)), '/'));
        $target = realpath($root . DIRECTORY_SEPARATOR . $clean);

        if ($target === false || strpos($target, $root) !== 0 || !is_file($target)) {
            // WebP denemesi
            $targetWebp = realpath($root . DIRECTORY_SEPARATOR . pathinfo($clean, PATHINFO_FILENAME) . '.webp');
            if ($targetWebp !== false && strpos($targetWebp, $root) === 0 && is_file($targetWebp)) {
                $target = $targetWebp;
            } else {
                throw new NotFoundException('Media asset not found.');
            }
        }

        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $this->sendFile($target, $extension);
    }

    private function sendFile(string $target, string $extension): void
    {
        $mime = self::ASSET_MIME_TYPES[$extension] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($target));
        header('Cache-Control: public, max-age=3600');
        readfile($target);
        exit;
    }

    private function findDirectory(string $name): ?string
    {
        foreach (scandir($this->templatesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (strtolower($entry) === strtolower($name) && is_dir($this->templatesPath . DIRECTORY_SEPARATOR . $entry)) {
                return $entry;
            }
        }

        return null;
    }

    private function activePath(): string
    {
        return $this->templatesPath . DIRECTORY_SEPARATOR . $this->activeDir;
    }
}
