<?php

declare(strict_types=1);

namespace Plugins\Admin\Services;

use App\Core\Config;
use RuntimeException;
use ZipArchive;

final class ZipInstallerService
{
    private string $basePath;

    public function __construct()
    {
        $base = Config::get('app.base_path');
        if ($base === null) {
            $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if (str_ends_with($base, '/public')) {
                $base = substr($base, 0, -strlen('/public'));
            }
        }
        // In local environments like ours, it's often better to just use $_SERVER['DOCUMENT_ROOT'] . '/groo' 
        // or a similar robust mechanism. For safety we rely on realpath.
        $this->basePath = realpath(__DIR__ . '/../../../') ?: (string) $base;
    }

    public function install(string $zipFilePath): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive eklentisi sunucuda yüklü değil.');
        }

        if (!is_file($zipFilePath) || !is_readable($zipFilePath)) {
            throw new RuntimeException('Yüklenen ZIP dosyası bulunamadı veya okunamıyor.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException('ZIP dosyası açılamadı. Geçerli bir ZIP dosyası olduğundan emin olun.');
        }

        $tempDirName = 'install_' . time() . '_' . bin2hex(random_bytes(4));
        $tempDir = $this->basePath . '/storage/temp/' . $tempDirName;

        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            $zip->close();
            throw new RuntimeException('Geçici dizin oluşturulamadı: ' . $tempDir);
        }

        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            $this->removeDirectory($tempDir);
            throw new RuntimeException('ZIP dosyası geçici dizine çıkartılamadı.');
        }
        $zip->close();

        try {
            $this->processExtractedDirectory($tempDir);
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    private function processExtractedDirectory(string $extractDir): void
    {
        // ZIP contents might be wrapped in a single root folder. 
        // We look for 'template' and 'plugins' folders recursively up to a certain depth.
        $templateDirs = $this->findDirectoriesByName($extractDir, 'template', 2);
        $pluginsDirs = $this->findDirectoriesByName($extractDir, 'plugins', 2);

        if (empty($templateDirs) && empty($pluginsDirs)) {
            throw new RuntimeException('ZIP dosyası içerisinde geçerli bir "template" veya "plugins" klasörü bulunamadı.');
        }

        foreach ($templateDirs as $tDir) {
            $this->moveSubdirectories($tDir, $this->basePath . '/templates');
        }

        foreach ($pluginsDirs as $pDir) {
            $this->moveSubdirectories($pDir, $this->basePath . '/plugins');
        }
    }

    private function moveSubdirectories(string $sourceDir, string $targetBaseDir): void
    {
        if (!is_dir($targetBaseDir) && !mkdir($targetBaseDir, 0755, true) && !is_dir($targetBaseDir)) {
            throw new RuntimeException('Hedef dizin oluşturulamadı: ' . $targetBaseDir);
        }

        $items = scandir($sourceDir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $sourceDir . '/' . $item;
            if (!is_dir($sourcePath)) {
                continue;
            }

            // Güvenlik kontrolü (Path Traversal)
            $safeFolderName = basename($item);
            if ($safeFolderName === '' || $safeFolderName === '.' || $safeFolderName === '..') {
                continue;
            }

            $targetPath = $targetBaseDir . '/' . $safeFolderName;

            // Eğer klasör zaten varsa, içeriğini kopyalayalım veya silelim?
            // Basitlik için mevcutsa hata fırlatıyoruz veya üzerine yazıyoruz (içeriği birleştirerek).
            // Temaları ve eklentileri üzerine yazmak için klasör kopyalama fonksiyonunu kullanalım.
            $this->copyDirectory($sourcePath, $targetPath);
        }
    }

    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new RuntimeException('Dizin kopyalanamadı: ' . $dest);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                    throw new RuntimeException('Klasör oluşturulamadı: ' . $target);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    private function findDirectoriesByName(string $baseDir, string $targetName, int $maxDepth, int $currentDepth = 0): array
    {
        $found = [];

        if ($currentDepth > $maxDepth) {
            return $found;
        }

        $items = @scandir($baseDir);
        if ($items === false) {
            return $found;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $baseDir . '/' . $item;
            if (is_dir($path)) {
                if (strtolower($item) === strtolower($targetName)) {
                    $found[] = $path;
                } else {
                    $found = array_merge($found, $this->findDirectoriesByName($path, $targetName, $maxDepth, $currentDepth + 1));
                }
            }
        }

        return $found;
    }
}
