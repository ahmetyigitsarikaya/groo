<?php

declare(strict_types=1);

namespace Plugins\Traffic\Services;

use App\Core\Database\Connection;

/**
 * ScoreCalculator - Sayfaların trafik kalitesine göre Başarı Puanı hesaplar.
 */
final class ScoreCalculator
{
    /**
     * Belirli bir URL'nin başarı puanını hesaplar ve DB'ye yazar.
     */
    public static function calculateForUrl(string $url): int
    {
        $pdo = Connection::getInstance();
        
        $stmt = $pdo->prepare('SELECT total_views, unique_views, bot_views FROM page_metrics WHERE url = ?');
        $stmt->execute([$url]);
        $metrics = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$metrics) {
            return 0;
        }

        $totalViews  = (int) $metrics['total_views'];
        $uniqueViews = (int) $metrics['unique_views'];
        $botViews    = (int) $metrics['bot_views'];
        
        $totalAll = $totalViews + $botViews;

        if ($totalAll === 0) {
            return 0;
        }

        $score = 0.0;

        // 1. İnsan Trafiği Oranı (Max 50 puan)
        // İnsan trafiği = total_views (çünkü total_views sadece insanları sayar - eklentide böyle kurguladık)
        $humanRatio = $totalViews / $totalAll;
        $score += ($humanRatio * 50);

        // 2. Tekil Ziyaretçi Oranı (Max 50 puan)
        if ($totalViews > 0) {
            $uniqueRatio = $uniqueViews / $totalViews;
            $score += ($uniqueRatio * 50);
        }

        // 3. Negatif Etken (Bot Oranı %70'den fazlaysa ceza puanı)
        $botRatio = $botViews / $totalAll;
        if ($botRatio > 0.70) {
            // Örnek: %80 bot ise, (0.80 - 0.70) * 100 = 10 puan ceza
            $penalty = ($botRatio - 0.70) * 100;
            $score -= $penalty;
        }

        // Sınırlandırma (0-100 arası)
        $finalScore = (int) round($score);
        if ($finalScore < 0) {
            $finalScore = 0;
        } elseif ($finalScore > 100) {
            $finalScore = 100;
        }

        // Güncelle
        $upd = $pdo->prepare('UPDATE page_metrics SET success_score = ? WHERE url = ?');
        $upd->execute([$finalScore, $url]);

        return $finalScore;
    }

    /**
     * Tüm sayfaların başarı puanını yeniden hesaplar. (Cron/Admin Dashboard için)
     */
    public static function recalculateAll(): void
    {
        $pdo = Connection::getInstance();
        $urls = $pdo->query('SELECT url FROM page_metrics')->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($urls as $url) {
            self::calculateForUrl((string) $url);
        }
    }
}
