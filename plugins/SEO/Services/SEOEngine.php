<?php

declare(strict_types=1);

namespace Plugins\SEO\Services;

use App\Core\Database\Connection;
use Plugins\SEO\Helpers\Cache;

final class SEOEngine
{
    private array $settings;

    public function __construct()
    {
        $this->settings = $this->loadSettings();
    }

    private function loadSettings(): array
    {
        $cached = Cache::get('seo_settings');
        if ($cached !== null) {
            return $cached;
        }

        $pdo = Connection::getInstance();
        $stmt = $pdo->query("SELECT `key`, `value` FROM seo_settings");
        $results = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        Cache::set('seo_settings', $results, 86400); // 1 day
        return $results;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSEODataForUrl(string $url): array
    {
        // Temiz URL: Query string'den arındır
        $cleanUrl = parse_url($url, PHP_URL_PATH) ?? '/';

        $cached = Cache::get('seo_meta_' . $cleanUrl);
        if ($cached !== null) {
            return $this->applyFallback($cached, $cleanUrl);
        }

        $pdo = Connection::getInstance();
        
        // Önce tam eşleşme
        $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE url_pattern = ? LIMIT 1");
        $stmt->execute([$cleanUrl]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            // Wildcard eşleşme ara (örneğin /blog/*)
            // Bu basit yaklaşımda sadece % sonuna ekleyerek basit wildcard destekliyoruz.
            // Fakat sistemde tam pattern regex tutulmuyorsa, basit LIKE kullanacağız.
            $stmtWild = $pdo->prepare("SELECT * FROM seo_meta WHERE ? LIKE REPLACE(url_pattern, '*', '%') AND url_pattern LIKE '%*%' ORDER BY LENGTH(url_pattern) DESC LIMIT 1");
            $stmtWild->execute([$cleanUrl]);
            $row = $stmtWild->fetch(\PDO::FETCH_ASSOC);
        }

        $result = $row ?: [];
        Cache::set('seo_meta_' . $cleanUrl, $result, 3600); // 1 hour
        
        return $this->applyFallback($result, $cleanUrl);
    }

    private function applyFallback(array $meta, string $cleanUrl): array
    {
        $separator = $this->getSetting('separator', '|');
        $siteName = 'Groo'; // Bu, genel sistem ayarlarından çekilebilir ancak şimdilik sabit tutalım veya settings'e ekleyelim
        
        // Eğer veritabanında seo_meta kaydı yoksa veya eksikse tamamla
        $title = !empty($meta['title']) ? $meta['title'] : 'Sayfa Bulunamadı'; 
        
        // Title fallback
        if (empty($meta['title'])) {
            // Gerçekçi bir fallback için, örneğin HTML içeriğinden başlık yakalamak idealdir ama bu noktada o yok.
            $meta['title'] = "Sayfa " . $separator . " " . $siteName;
        }

        if (!isset($meta['canonical_url']) || $meta['canonical_url'] === '') {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = (string) \App\Core\Config::get('app.base_path', '');
            $baseUrl = rtrim($scheme . '://' . $host . $basePath, '/');
            $meta['canonical_url'] = $baseUrl . $cleanUrl;
        }

        if (empty($meta['og_image'])) {
            $meta['og_image'] = $this->getSetting('default_og_image');
        }

        if (empty($meta['og_title'])) {
            $meta['og_title'] = $meta['title'];
        }

        if (empty($meta['og_description'])) {
            $meta['og_description'] = $meta['description'] ?? '';
        }

        return $meta;
    }
}
