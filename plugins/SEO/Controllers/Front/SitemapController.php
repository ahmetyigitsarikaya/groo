<?php

declare(strict_types=1);

namespace Plugins\SEO\Controllers\Front;

use App\Core\Database\Connection;
use Plugins\SEO\Helpers\Cache;
use Plugins\SEO\Services\SEOEngine;

final class SitemapController
{
    public function robots(array $params): void
    {
        $engine = new \Plugins\SEO\Services\SEOEngine();
        $content = $engine->getSetting('robots_txt_content', "User-agent: *\nAllow: /");

        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = (string) \App\Core\Config::get('app.base_path', '');
        $baseUrl = rtrim($scheme . '://' . $host . $basePath, '/');
        
        $sitemapUrl = $baseUrl . '/sitemap.xml';
        
        $adminPath = (string) \App\Core\Config::get('app.admin_path', 'admin');
        if (strpos($content, 'Disallow: /' . $adminPath) === false) {
            $content .= "\n\n# Admin pages\nDisallow: /" . $adminPath . "/\n";
        }

        if (strpos($content, 'Sitemap:') === false) {
            $content .= "\nSitemap: " . $sitemapUrl;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
    }

    public function sitemap(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $cached = Cache::get('sitemap_xml');
        if ($cached !== null) {
            echo $cached;
            return;
        }

        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = (string) \App\Core\Config::get('app.base_path', '');
        $baseUrl = rtrim($scheme . '://' . $host . $basePath, '/');

        $pdo = Connection::getInstance();
        $urls = [];

        // 1. Statik Rotalar veya Sabit Sayfalar
        $urls[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => date('Y-m-d\TH:i:sP'),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];

        // 2. SEO Meta Rotaları (NoIndex olmayanlar)
        $stmtMeta = $pdo->query("SELECT url_pattern, updated_at FROM seo_meta WHERE noindex = 0 AND url_pattern NOT LIKE '%*%'");
        if ($stmtMeta) {
            while ($row = $stmtMeta->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['url_pattern'] !== '/') {
                    $urls[] = [
                        'loc' => $baseUrl . $row['url_pattern'],
                        'lastmod' => date('Y-m-d\TH:i:sP', strtotime($row['updated_at'])),
                        'changefreq' => 'weekly',
                        'priority' => '0.8'
                    ];
                }
            }
        }

        // 3. Blog Yazıları (Content Plugin kuruluysa)
        try {
            $stmtContent = $pdo->query("SELECT slug, updated_at FROM content_posts WHERE status = 'published'");
            if ($stmtContent) {
                while ($row = $stmtContent->fetch(\PDO::FETCH_ASSOC)) {
                    $urls[] = [
                        'loc' => $baseUrl . '/blog/' . $row['slug'], // Örnek blog route'u
                        'lastmod' => date('Y-m-d\TH:i:sP', strtotime($row['updated_at'])),
                        'changefreq' => 'weekly',
                        'priority' => '0.7'
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Content tablosu yoksa yoksay
        }

        // 4. XML Çıktısı oluştur
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url['loc']);
            $xml->writeElement('lastmod', $url['lastmod']);
            $xml->writeElement('changefreq', $url['changefreq']);
            $xml->writeElement('priority', $url['priority']);
            $xml->endElement(); // url
        }

        $xml->endElement(); // urlset
        $xml->endDocument();

        $content = $xml->outputMemory();
        Cache::set('sitemap_xml', $content, 21600); // 6 hours

        echo $content;
    }
}
