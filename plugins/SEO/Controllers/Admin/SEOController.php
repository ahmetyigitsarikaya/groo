<?php

declare(strict_types=1);

namespace Plugins\SEO\Controllers\Admin;

use App\Core\Database\Connection;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;
use Plugins\SEO\Helpers\Cache;

final class SEOController extends BaseController
{
    public function index(array $params): void
    {
        $pdo = Connection::getInstance();
        
        // SEO Metas
        $stmtMeta = $pdo->query("SELECT * FROM seo_meta ORDER BY id DESC");
        $metas = $stmtMeta->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // SEO Settings
        $stmtSet = $pdo->query("SELECT `key`, `value` FROM seo_settings");
        $settings = $stmtSet->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        $this->render('admin-seo', [
            'metas' => $metas,
            'settings' => $settings,
            'title' => 'SEO Yönetimi'
        ]);
    }

    public function saveSettings(array $params): void
    {
        $this->requireCsrf();

        $pdo = Connection::getInstance();
        $settings = [
            'separator' => $_POST['separator'] ?? '|',
            'default_og_image' => $_POST['default_og_image'] ?? '',
            'google_site_verification' => $_POST['google_site_verification'] ?? '',
            'bing_site_verification' => $_POST['bing_site_verification'] ?? '',
        ];

        $stmt = $pdo->prepare("UPDATE seo_settings SET `value` = ? WHERE `key` = ?");
        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }

        Cache::clear();
        \App\Core\Middleware\PageCache::flush();
        Flash::set('success', 'Genel SEO ayarları güncellendi ve önbellek temizlendi.');
        $this->redirect('/admin/seo');
    }

    public function saveRobots(array $params): void
    {
        $this->requireCsrf();

        $robotsTxt = $_POST['robots_txt_content'] ?? '';
        $pdo = Connection::getInstance();
        
        $stmt = $pdo->prepare("UPDATE seo_settings SET `value` = ? WHERE `key` = 'robots_txt_content'");
        $stmt->execute([$robotsTxt]);

        Cache::clear();
        \App\Core\Middleware\PageCache::flush();
        Flash::set('success', 'Robots.txt kuralları güncellendi.');
        $this->redirect('/admin/seo');
    }

    public function storeMeta(array $params): void
    {
        $this->requireCsrf();

        $url = rtrim($_POST['url_pattern'] ?? '', '/');
        if ($url === '') {
            $url = '/';
        }

        $pdo = Connection::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO seo_meta (url_pattern, title, description, keywords, og_title, og_description, og_image, noindex, nofollow, schema_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                keywords = VALUES(keywords),
                og_title = VALUES(og_title),
                og_description = VALUES(og_description),
                og_image = VALUES(og_image),
                noindex = VALUES(noindex),
                nofollow = VALUES(nofollow),
                schema_type = VALUES(schema_type)
        ");

        $stmt->execute([
            $url,
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            $_POST['keywords'] ?? '',
            $_POST['og_title'] ?? '',
            $_POST['og_description'] ?? '',
            $_POST['og_image'] ?? '',
            isset($_POST['noindex']) ? 1 : 0,
            isset($_POST['nofollow']) ? 1 : 0,
            $_POST['schema_type'] ?? ''
        ]);

        Cache::clear();
        \App\Core\Middleware\PageCache::flush();
        Flash::set('success', 'SEO Meta kuralları kaydedildi.');
        $this->redirect('/admin/seo');
    }

    public function deleteMeta(array $params): void
    {
        $this->requireCsrf();
        $id = (int) ($params['id'] ?? 0);

        if ($id > 0) {
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare("DELETE FROM seo_meta WHERE id = ?");
            $stmt->execute([$id]);
            Cache::clear();
            \App\Core\Middleware\PageCache::flush();
            Flash::set('success', 'SEO Meta kuralı silindi.');
        }

        $this->redirect('/admin/seo');
    }

    public function clearCache(array $params): void
    {
        Cache::clear();
        \App\Core\Middleware\PageCache::flush();
        Flash::set('success', 'SEO önbelleği başarıyla temizlendi.');
        $this->redirect('/admin/seo');
    }
}
