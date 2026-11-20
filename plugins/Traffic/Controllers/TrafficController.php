<?php

declare(strict_types=1);

namespace Plugins\Traffic\Controllers;

use App\Core\Database\QueryBuilder;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Traffic\Services\ScoreCalculator;

/**
 * TrafficController - Admin paneli trafik analiz sayfası.
 */
final class TrafficController extends BaseController
{
    public function index(array $params): void
    {
        // Sadece yetkili adminler
        if (!$this->auth->can('admin.traffic') && !$this->auth->can('*')) {
            $this->redirect('/admin');
        }

        // 1. Ziyaret edilmeden önce tüm başarı puanlarını anlık (on-the-fly) hesapla
        ScoreCalculator::recalculateAll();

        // 2. Özet İstatistikleri Hesapla
        $pdo = \App\Core\Database\Connection::getInstance();
        
        $excludeAdmin = isset($_COOKIE['exclude_admin_traffic']) && $_COOKIE['exclude_admin_traffic'] === '1';
        $basePath = rtrim((string) \App\Core\Config::get('app.base_path', ''), '/');
        $adminPath = ltrim((string) \App\Core\Config::get('app.admin_path', 'admin'), '/');
        
        // Escape for SQL LIKE (using double backslash for PHP string)
        $likeAdminPath = $basePath . '/' . $adminPath . '%';
        
        $whereMetrics = $excludeAdmin ? "WHERE url NOT LIKE :admin_path" : "";
        $whereLogsAnd = $excludeAdmin ? "AND url NOT LIKE :admin_path" : "";
        $whereLogs    = $excludeAdmin ? "WHERE url NOT LIKE :admin_path" : "";

        // 2. Özet İstatistikleri Hesapla
        $stmtTotals = $pdo->prepare("
            SELECT 
                COALESCE(SUM(total_views), 0) as total_human,
                COALESCE(SUM(bot_views), 0) as total_bot
            FROM page_metrics
            $whereMetrics
        ");
        if ($excludeAdmin) $stmtTotals->bindParam(':admin_path', $likeAdminPath);
        $stmtTotals->execute();
        $totals = $stmtTotals->fetch(\PDO::FETCH_ASSOC);

        $totalHuman = (int) ($totals['total_human'] ?? 0);
        $totalBot   = (int) ($totals['total_bot'] ?? 0);
        $grandTotal = $totalHuman + $totalBot;

        $humanPercent = $grandTotal > 0 ? round(($totalHuman / $grandTotal) * 100, 1) : 0;
        $botPercent   = $grandTotal > 0 ? round(($totalBot / $grandTotal) * 100, 1) : 0;

        $stmtTop = $pdo->prepare("
            SELECT url, success_score 
            FROM page_metrics 
            $whereMetrics
            ORDER BY success_score DESC, total_views DESC 
            LIMIT 1
        ");
        if ($excludeAdmin) $stmtTop->bindParam(':admin_path', $likeAdminPath);
        $stmtTop->execute();
        $topPage = $stmtTop->fetch(\PDO::FETCH_ASSOC);

        // 3. Günlük Trafik (Chart.js İçin) - Son 7 gün
        $stmtChart = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN is_bot = 0 THEN 1 ELSE 0 END) as humans,
                SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bots
            FROM traffic_logs
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) $whereLogsAnd
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        if ($excludeAdmin) $stmtChart->bindParam(':admin_path', $likeAdminPath);
        $stmtChart->execute();
        $chartData = $stmtChart->fetchAll(\PDO::FETCH_ASSOC);

        $labels = [];
        $humans = [];
        $bots   = [];
        foreach ($chartData as $row) {
            $labels[] = $row['date'];
            $humans[] = (int) $row['humans'];
            $bots[]   = (int) $row['bots'];
        }

        // 4. Sayfa Performans Tablosu (En çok hit alandan en aza)
        $stmtPages = $pdo->prepare("
            SELECT url, total_views, unique_views, bot_views, success_score
            FROM page_metrics
            $whereMetrics
            ORDER BY total_views DESC
            LIMIT 50
        ");
        if ($excludeAdmin) $stmtPages->bindParam(':admin_path', $likeAdminPath);
        $stmtPages->execute();
        $pages = $stmtPages->fetchAll(\PDO::FETCH_ASSOC);

        // 5. Canlı Akış (Son 20 istek)
        $stmtLive = $pdo->prepare("
            SELECT ip_address, url, is_bot, bot_name, device_type, created_at
            FROM traffic_logs
            $whereLogs
            ORDER BY id DESC
            LIMIT 20
        ");
        if ($excludeAdmin) $stmtLive->bindParam(':admin_path', $likeAdminPath);
        $stmtLive->execute();
        $liveFeed = $stmtLive->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin-traffic', [
            'grand_total'   => $grandTotal,
            'human_percent' => $humanPercent,
            'bot_percent'   => $botPercent,
            'top_page'      => $topPage,
            'chart_labels'  => $labels,
            'chart_humans'  => $humans,
            'chart_bots'    => $bots,
            'pages'         => $pages,
            'live_feed'     => $liveFeed
        ]);
    }
}
