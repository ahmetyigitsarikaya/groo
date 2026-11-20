<?php

declare(strict_types=1);

namespace Plugins\Traffic;

use App\Core\Config;
use App\Core\Database\QueryBuilder;
use App\Core\Http\Router;
use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Traffic\Helpers\BotDetector;

final class TrafficPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'Traffic Analytics',
        'slug' => 'traffic',
        'version' => '1.0.0',
        'description' => 'Anlık trafik izleme, bot/insan ayrımı ve sayfa başarı puanı algoritması (Success Score).',
        'requires' => ['admin'],
    ];

    public function getMetadata(): array
    {
        $meta = parent::getMetadata();
        $meta['route_prefix'] = \Plugins\Admin\Support\AdminPaths::path('/traffic');
        return $meta;
    }

    public function boot(): void
    {
        $this->events()->listen('app.booted', function ($app) {
            $adminPath = (string) Config::get('app.admin_path', 'admin');
            $this->registerTrafficLogger($app->getRouter(), $adminPath);
        });
    }

    public function register(): void
    {
        View::addPath($this->path('Views'));
        
        $adminPath = (string) Config::get('app.admin_path', 'admin');
        \Plugins\Admin\Support\AdminMenu::add('Traffic Analytics', '/' . $adminPath . '/traffic', 'icon-users', 45, 'admin.traffic');
    }

    private function registerTrafficLogger(Router $router, string $adminPath): void
    {
        /**
         * Bramus Router'ın global before kancasını kullanıyoruz.
         * Tüm GET isteklerini yakalar.
         */
        $router->before('GET', '/.*', function () use ($adminPath) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?? '/';

            $basePath = rtrim((string) \App\Core\Config::get('app.base_path', ''), '/');
            $fullAdminPath = $basePath . '/' . ltrim($adminPath, '/');

            // "Admin trafiğini hariç tut" ayarı (çerez tabanlı)
            if (isset($_COOKIE['exclude_admin_traffic']) && $_COOKIE['exclude_admin_traffic'] === '1') {
                if (strpos($path, $fullAdminPath) === 0) {
                    return; // pass
                }
            }

            // Her zaman hariç tutulacak olanlar
            if (
                strpos($path, $basePath . '/api/') === 0 || 
                strpos($path, $basePath . '/assets/') === 0
            ) {
                return; // pass
            }

            // Statik dosyaları atla (favicon, css, js)
            if (preg_match('/\.(css|js|ico|jpg|jpeg|png|gif|svg|woff|woff2|ttf)$/i', $path)) {
                return;
            }

            // Asenkron çalıştırılamaz PHP kısıtlaması nedeniyle hızlı insert işlemi
            $this->logTraffic($path);
        });
    }

    private function logTraffic(string $path): void
    {
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer   = $_SERVER['HTTP_REFERER'] ?? null;

        $detector = new BotDetector($userAgent);
        $isBot    = $detector->isBot() ? 1 : 0;
        $botName  = $detector->getBotName();
        $device   = $detector->getDeviceType();

        try {
            // 1. Log tablosuna ekle
            QueryBuilder::table('traffic_logs')->insert([
                'ip_address'  => $ip,
                'url'         => $path,
                'referer'     => $referer,
                'user_agent'  => $userAgent,
                'is_bot'      => $isBot,
                'bot_name'    => $botName,
                'device_type' => $device,
            ]);

            // 2. Metrics tablosunu güncelle (Upsert / ON DUPLICATE KEY UPDATE)
            $pdo = \App\Core\Database\Connection::getInstance();
            
            // Son 24 saat içinde bu IP'den aynı URL'e girilmiş mi kontrol et (Unique ziyaret sayacı için)
            // Performans için sadece gerçek kullanıcılarda unique sayıyoruz
            $uniqueIncrement = 0;
            if ($isBot === 0) {
                $checkUnique = $pdo->prepare('
                    SELECT id FROM traffic_logs 
                    WHERE url = ? AND ip_address = ? AND is_bot = 0 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    LIMIT 2
                ');
                $checkUnique->execute([$path, $ip]);
                $visits = $checkUnique->fetchAll();
                // 1 kayıt varsa (az önce insert ettiğimiz kayıt), o zaman unique bir ziyarettir.
                if (count($visits) === 1) {
                    $uniqueIncrement = 1;
                }
            }

            $totalInc  = $isBot === 0 ? 1 : 0;
            $botInc    = $isBot === 1 ? 1 : 0;

            $upsert = $pdo->prepare('
                INSERT INTO page_metrics (url, total_views, unique_views, bot_views, success_score) 
                VALUES (?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE 
                    total_views = total_views + ?, 
                    unique_views = unique_views + ?, 
                    bot_views = bot_views + ?,
                    last_visited_at = CURRENT_TIMESTAMP
            ');
            $upsert->execute([
                $path, $totalInc, $uniqueIncrement, $botInc, // Insert
                $totalInc, $uniqueIncrement, $botInc         // Update
            ]);

        } catch (\Throwable $e) {
            // Hata olursa sessizce devam et (Trafik loglama ana akışı kesmemeli)
            error_log('[Traffic Plugin] Log Error: ' . $e->getMessage());
        }
    }
}
