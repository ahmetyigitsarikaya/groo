<?php

declare(strict_types=1);

namespace Plugins\Media\Controllers;

use App\Core\Config;
use App\Core\Database\Connection;
use Plugins\Admin\Controllers\BaseController;

use Plugins\Admin\Support\Flash;

final class MediaController extends BaseController
{
    private static function generateResponsiveWebp($image, $originalWidth, $originalHeight, $baseDestination): void
    {
        $sizes = [
            '480w' => 480,
            '768w' => 768,
        ];

        $pathInfo = pathinfo($baseDestination);
        $dir = $pathInfo['dirname'];
        $name = $pathInfo['filename'];
        $ext = $pathInfo['extension'];

        foreach ($sizes as $suffix => $targetWidth) {
            if ($originalWidth > $targetWidth) {
                $targetHeight = (int) round($originalHeight * ($targetWidth / $originalWidth));
                $resized = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
                
                $responsiveDest = $dir . '/' . $name . '_' . $suffix . '.' . $ext;
                @imagewebp($resized, $responsiveDest, 80);
                @imagedestroy($resized);
            }
        }
    }

    public function index(): void
    {
        $pdo = Connection::getInstance();
        $stmt = $pdo->query("SELECT * FROM media_library ORDER BY created_at DESC");
        $media = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin/index', ['media' => $media]);
    }

    public function list(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Connection::getInstance();
            $stmt = $pdo->query("SELECT * FROM media_library ORDER BY created_at DESC");
            $media = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'media' => $media]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete(array $params): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('error', 'Geçersiz ID.');
            $this->redirect('/admin/media');
            return;
        }

        $pdo = Connection::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM media_library WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($media) {
            $baseDir = rtrim(GROO_BASE_PATH, '/\\');
            // Assuming url format is like /groo/public/uploads/...
            // We need to extract the physical file path.
            // The safest way is using the filename column.
            $physicalPath = $baseDir . '/public/uploads/' . $media['filename'];
            if (file_exists($physicalPath)) {
                @unlink($physicalPath);
            }

            $delStmt = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
            $delStmt->execute([$id]);
            Flash::set('success', 'Medya başarıyla silindi.');
        } else {
            Flash::set('error', 'Medya bulunamadı.');
        }

        $this->redirect('/admin/media');
    }

    public function upload(array $params): void
    {
        error_log("GROO_DEBUG: MediaController::upload started.");
        header('Content-Type: application/json');

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            error_log("GROO_DEBUG: No file uploaded.");
            echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi.']);
            exit;
        }

        $file = $_FILES['file'];
        $tmpPath = $file['tmp_name'];
        
        $mime = mime_content_type($tmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowedMimes, true)) {
            echo json_encode(['success' => false, 'message' => 'Sadece JPG, PNG veya WebP görselleri desteklenir.']);
            exit;
        }

        $baseDir = defined('GROO_BASE_PATH') ? rtrim(GROO_BASE_PATH, '/\\') : rtrim(dirname(__DIR__, 3), '/\\');
        $uploadDir = $baseDir . '/public/uploads';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('img_') . '.webp';
        $destination = $uploadDir . '/' . $filename;
        $url = (Config::get('app.base_path') ?? '') . '/public/uploads/' . $filename;

        // Boyutları al
        $dimensions = @getimagesize($tmpPath);
        $width = $dimensions ? $dimensions[0] : null;
        $height = $dimensions ? $dimensions[1] : null;

        try {
            $hasGD = function_exists('imagecreatefrompng') && function_exists('imagewebp');
            $image = false;

            if ($hasGD) {
                if ($mime === 'image/jpeg') {
                    $image = @imagecreatefromjpeg($tmpPath);
                } elseif ($mime === 'image/png') {
                    $image = @imagecreatefrompng($tmpPath);
                    if ($image !== false) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                } elseif ($mime === 'image/webp') {
                    $image = @imagecreatefromwebp($tmpPath);
                }
            }

            if ($image !== false) {
                // WebP olarak %80 kalitede kaydet
                imagewebp($image, $destination, 80);
                if ($width && $height) {
                    self::generateResponsiveWebp($image, $width, $height, $destination);
                }
                imagedestroy($image);
                $finalMime = 'image/webp';
            } else {
                // GD yoksa veya okuyamazsa orijinal dosyayı taşı
                $filename = uniqid('img_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $destination = $uploadDir . '/' . $filename;
                $url = (Config::get('app.base_path') ?? '') . '/public/uploads/' . $filename;
                move_uploaded_file($tmpPath, $destination);
                $finalMime = $mime;
            }

            $size = filesize($destination) ?: 0;

            // DB'ye kaydet
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare("INSERT INTO media_library (filename, url, mime_type, width, height, size_bytes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$filename, $url, $finalMime, $width, $height, $size]);

            echo json_encode([
                'success' => true,
                'url' => $url,
                'width' => $width,
                'height' => $height
            ]);

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'İşlem hatası: ' . $e->getMessage()]);
        }
        
        exit;
    }

    public function optimizeThemeAssets(array $params): void
    {
        $baseDir = defined('GROO_BASE_PATH') ? rtrim(GROO_BASE_PATH, '/\\') : rtrim(dirname(__DIR__, 3), '/\\');
        $themesDir = $baseDir . '/templates';
        $importedTotal = 0;

        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                if (is_dir($themesDir . '/' . $entry)) {
                    $importedTotal += self::processThemeAssets($entry);
                }
            }
        }

        Flash::set('success', $importedTotal . ' adet tema görseli WebP formatına dönüştürülüp medya kütüphanesine eklendi.');
        $this->redirect('/admin/media');
    }

    public static function processThemeAssets(string $themeSlug): int
    {
        $baseDir = defined('GROO_BASE_PATH') ? rtrim(GROO_BASE_PATH, '/\\') : rtrim(dirname(__DIR__, 3), '/\\');
        $themeDir = $baseDir . '/templates/' . $themeSlug;
        if (!is_dir($themeDir)) {
            return 0;
        }

        $uploadThemeDir = $baseDir . '/public/uploads/themes/' . $themeSlug;
        if (!is_dir($uploadThemeDir)) {
            @mkdir($uploadThemeDir, 0755, true);
        }

        $pdo = Connection::getInstance();
        $basePath = (string)(Config::get('app.base_path') ?? '');
        $imported = 0;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($themeDir));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;

            $sourcePath = $file->getPathname();
            $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
            $hash = substr(md5($sourcePath), 0, 6);
            $webpFilename = $baseName . '_' . $hash . '.webp';
            $destination = $uploadThemeDir . '/' . $webpFilename;
            $url = $basePath . '/public/uploads/themes/' . $themeSlug . '/' . $webpFilename;

            $hasGD = function_exists('imagecreatefrompng') && function_exists('imagewebp');
            $converted = false;

            if ($hasGD) {
                $image = false;
                if ($ext === 'jpg' || $ext === 'jpeg') {
                    if (function_exists('imagecreatefromjpeg')) {
                        $image = @imagecreatefromjpeg($sourcePath);
                    }
                } elseif ($ext === 'png') {
                    if (function_exists('imagecreatefrompng')) {
                        $image = @imagecreatefrompng($sourcePath);
                        if ($image !== false) {
                            imagepalettetotruecolor($image);
                            imagealphablending($image, true);
                            imagesavealpha($image, true);
                        }
                    }
                } elseif ($ext === 'webp') {
                    if (function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($sourcePath);
                    }
                }

                if ($image !== false && $image !== null) {
                    @imagewebp($image, $destination, 80);
                    $dims = @getimagesize($sourcePath);
                    if ($dims) {
                        self::generateResponsiveWebp($image, $dims[0], $dims[1], $destination);
                    }
                    @imagedestroy($image);
                    $converted = true;
                }
            }

            if (!$converted) {
                $webpFilename = $baseName . '_' . $hash . '.' . $ext;
                $destination = $uploadThemeDir . '/' . $webpFilename;
                $url = $basePath . '/public/uploads/themes/' . $themeSlug . '/' . $webpFilename;
                copy($sourcePath, $destination);
            }

            $dims = @getimagesize($destination);
            $w = $dims ? $dims[0] : null;
            $h = $dims ? $dims[1] : null;
            $size = filesize($destination) ?: 0;
            $mime = $converted ? 'image/webp' : ('image/' . ($ext === 'jpg' ? 'jpeg' : $ext));

            $stmt = $pdo->prepare("SELECT id FROM media_library WHERE url = ?");
            $stmt->execute([$url]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("INSERT INTO media_library (filename, url, mime_type, width, height, size_bytes) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$webpFilename, $url, $mime, $w, $h, $size]);
                $imported++;
            }
        }

        return $imported;
    }
}
