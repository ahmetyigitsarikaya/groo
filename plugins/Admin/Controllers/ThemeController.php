<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\Config;
use Plugins\Admin\Services\ZipInstallerService;
use Plugins\Admin\Support\Flash;
use Throwable;

final class ThemeController extends BaseController
{
    private ZipInstallerService $installer;

    public function __construct()
    {
        parent::__construct();
        $this->installer = new ZipInstallerService();
    }

    public function index(array $params = []): void
    {
        $basePath = $this->getBasePath();
        $templatesDir = $basePath . '/templates';
        $activeTemplate = Config::get('env.ACTIVE_TEMPLATE', 'default');
        
        $templates = [];
        if (is_dir($templatesDir)) {
            $items = scandir($templatesDir);
            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    if (is_dir($templatesDir . '/' . $item)) {
                        $templates[] = [
                            'slug' => $item,
                            'name' => ucfirst($item),
                            'is_active' => $item === $activeTemplate
                        ];
                    }
                }
            }
        }

        $this->render('themes/index', [
            'templates' => $templates,
            'active_template' => $activeTemplate
        ]);
    }

    public function upload(array $params = []): void
    {
        $this->requireCsrf();

        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            Flash::set('error', 'Dosya yüklenirken bir hata oluştu veya dosya seçilmedi.');
            $this->redirect('/admin/themes');
            return;
        }

        $file = $_FILES['zip_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            Flash::set('error', 'Lütfen sadece .zip formatında dosya yükleyin.');
            $this->redirect('/admin/themes');
            return;
        }

        try {
            $this->installer->install($file['tmp_name']);
            Flash::set('success', 'Şablon / Eklenti başarıyla yüklendi.');
        } catch (Throwable $e) {
            Flash::set('error', 'Yükleme hatası: ' . $e->getMessage());
        }

        $this->redirect('/admin/themes');
    }

    public function activate(array $params = []): void
    {
        $this->requireCsrf();

        $slug = $params['slug'] ?? '';
        if ($slug === '') {
            Flash::set('error', 'Geçersiz şablon seçimi.');
            $this->redirect('/admin/themes');
            return;
        }
        
        $slug = basename($slug); // Prevent path traversal

        $basePath = $this->getBasePath();
        if (!is_dir($basePath . '/templates/' . $slug)) {
            Flash::set('error', 'Seçilen şablon dizini bulunamadı.');
            $this->redirect('/admin/themes');
            return;
        }

        $envFile = $basePath . '/.env';
        if (is_file($envFile) && is_writable($envFile)) {
            $content = file_get_contents($envFile);
            if ($content !== false) {
                if (preg_match('/^ACTIVE_TEMPLATE=.*$/m', $content)) {
                    $content = preg_replace('/^ACTIVE_TEMPLATE=.*$/m', 'ACTIVE_TEMPLATE=' . $slug, $content);
                } else {
                    $content .= "\nACTIVE_TEMPLATE=" . $slug . "\n";
                }
                file_put_contents($envFile, $content);
                if (class_exists('Plugins\Media\Controllers\MediaController')) {
                    \Plugins\Media\Controllers\MediaController::processThemeAssets($slug);
                }
                Flash::set('success', 'Şablon başarıyla aktifleştirildi ve görselleri WebP kütüphanesine aktarıldı.');
            } else {
                Flash::set('error', '.env dosyası okunamadı.');
            }
        } else {
            Flash::set('error', '.env dosyası bulunamadı veya yazılamaz durumda.');
        }

        $this->redirect('/admin/themes');
    }

    private function getBasePath(): string
    {
        $base = Config::get('app.base_path');
        if ($base === null) {
            $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if (str_ends_with($base, '/public')) {
                $base = substr($base, 0, -strlen('/public'));
            }
        }
        return realpath(__DIR__ . '/../../../') ?: (string) $base;
    }
}
