<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hook — WordPress tarzı Action/Filter kanca sistemi.
 *
 * Kullanım (action):
 *   Hook::action('user.created', function(array $user): void { ... });
 *   Hook::doAction('user.created', $userData);
 *
 * Kullanım (filter):
 *   Hook::filter('user_profile_tabs', function(array $tabs, int $userId): array {
 *       $tabs[] = ['id' => 'orders', 'label' => 'Siparişlerim', 'content' => '...'];
 *       return $tabs;
 *   });
 *   $tabs = Hook::applyFilter('user_profile_tabs', [], $userId);
 *
 * View içinde kısayol:
 *   Hook::apply('user_profile_tabs', $userId)  → applyFilter + echo HTML
 */
final class Hook
{
    /** @var array<string, array<int, callable[]>> */
    private static array $actions = [];

    /** @var array<string, array<int, callable[]>> */
    private static array $filters = [];

    // ----------------------------------------------------------------
    //  Actions
    // ----------------------------------------------------------------

    /**
     * Bir action kancasına callback ekler.
     *
     * @param string   $tag       Kanca adı, örn: 'user.created'
     * @param callable $callback  Çağrılacak fonksiyon
     * @param int      $priority  Düşük sayı = önce çalışır (default: 10)
     */
    public static function action(string $tag, callable $callback, int $priority = 10): void
    {
        self::$actions[$tag][$priority][] = $callback;
    }

    /**
     * Kayıtlı tüm action callback'lerini sırayla çalıştırır.
     *
     * @param string $tag     Kanca adı
     * @param mixed  ...$args Callback'lere geçirilecek argümanlar
     */
    public static function doAction(string $tag, ...$args): void
    {
        if (empty(self::$actions[$tag])) {
            return;
        }

        $groups = self::$actions[$tag];
        ksort($groups); // priority ascending

        foreach ($groups as $group) {
            foreach ($group as $callback) {
                $callback(...$args);
            }
        }
    }

    // ----------------------------------------------------------------
    //  Filters
    // ----------------------------------------------------------------

    /**
     * Bir filter kancasına callback ekler.
     *
     * @param string   $tag      Kanca adı, örn: 'user_profile_tabs'
     * @param callable $callback İlk parametre değeri alır, döndürür
     * @param int      $priority Düşük sayı = önce çalışır
     */
    public static function filter(string $tag, callable $callback, int $priority = 10): void
    {
        self::$filters[$tag][$priority][] = $callback;
    }

    /**
     * Filter kancasını çalıştırır; her callback sonucu bir sonrakine aktarır.
     *
     * @param string $tag     Kanca adı
     * @param mixed  $value   Başlangıç değeri
     * @param mixed  ...$args Ek argümanlar (değiştirilmeden geçirilir)
     * @return mixed          Filtrelenmiş son değer
     */
    public static function applyFilter(string $tag, $value, ...$args)
    {
        if (empty(self::$filters[$tag])) {
            return $value;
        }

        $groups = self::$filters[$tag];
        ksort($groups);

        foreach ($groups as $group) {
            foreach ($group as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * View şablonlarında kullanılan kısayol.
     * Filtrelenmiş değeri doğrudan echo eder (HTML çıktısı için).
     *
     * Örnek view kullanımı:
     *   <?php Hook::apply('user_profile_tabs', $userId); ?>
     *
     * @param string $tag     Kanca adı
     * @param mixed  ...$args İlk arg başlangıç değeri, geri kalanlar ek argüman
     */
    public static function apply(string $tag, ...$args): void
    {
        // İlk argüman başlangıç değeri (genellikle [] veya '' veya user_id)
        $initial = $args[0] ?? '';
        $extra   = array_slice($args, 1);

        $result = self::applyFilter($tag, $initial, ...$extra);

        if (is_array($result)) {
            foreach ($result as $item) {
                echo is_string($item) ? $item : '';
            }
        } elseif (is_string($result) || is_numeric($result)) {
            echo $result;
        }
    }

    // ----------------------------------------------------------------
    //  Yardımcılar
    // ----------------------------------------------------------------

    /** Bir kancaya kayıtlı callback sayısını döndürür. */
    public static function hasAction(string $tag): bool
    {
        return !empty(self::$actions[$tag]);
    }

    /** Bir filter kancasına kayıtlı callback var mı? */
    public static function hasFilter(string $tag): bool
    {
        return !empty(self::$filters[$tag]);
    }

    /** Tüm kancaları sıfırlar (test ortamları için). */
    public static function reset(): void
    {
        self::$actions = [];
        self::$filters = [];
    }
}
