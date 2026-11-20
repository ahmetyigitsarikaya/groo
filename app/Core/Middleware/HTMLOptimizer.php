<?php

declare(strict_types=1);

namespace App\Core\Middleware;

final class HTMLOptimizer
{
    public static function shouldOptimize(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $adminPath = $_ENV['ADMIN_PATH'] ?? 'admin';
        
        // Admin, API, Install yolları HTML optimize edilmez
        if (str_starts_with($uri, '/' . $adminPath) || str_starts_with($uri, '/api') || str_starts_with($uri, '/install')) {
            return false;
        }

        // HTML sayfası değilse optimize etme (Content-Type kontrolü yapılabilir, ancak Router genellikle HTML basıyor)
        return true;
    }

    public static function optimize(string $html): string
    {
        // Çok basit hata sayfasıysa dokunma
        if (strlen($html) < 100) return $html;
        if (!str_contains(strtolower($html), '<html')) return $html;

        // 1. <img> etiketlerine loading="lazy", decoding="async" ve WebP dönüşümü ekle
        $html = preg_replace_callback('/<img\s+([^>]+)>/i', function ($matches) {
            $attrs = $matches[1];
            $baseDir = rtrim(GROO_BASE_PATH, '/\\');

            // WebP Dönüşümü: Eğer src="/assets/img.png" ise ve "/assets/img.png.webp" varsa src'yi değiştir.
            if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                $src = $srcMatch[1];
                if (str_starts_with($src, '/') && (str_ends_with(strtolower($src), '.png') || str_ends_with(strtolower($src), '.jpg') || str_ends_with(strtolower($src), '.jpeg') || str_ends_with(strtolower($src), '.webp'))) {
                    // Extract path without query strings
                    $urlPath = parse_url($src, PHP_URL_PATH);
                    if ($urlPath) {
                        $physicalPath = $baseDir . $urlPath;
                        $webpPath = $physicalPath . '.webp';
                        $webpPathFinal = null;
                        $newSrcFinal = null;

                        $isAlreadyWebp = str_ends_with(strtolower($src), '.webp');

                        if ($isAlreadyWebp && file_exists($physicalPath)) {
                            $newSrcFinal = $src;
                            $webpPathFinal = $physicalPath;
                        } elseif (file_exists($webpPath)) {
                            $newSrcFinal = $src . '.webp';
                            $webpPathFinal = $webpPath;
                        } else {
                            $filename = pathinfo($urlPath, PATHINFO_FILENAME);
                            $basename = basename($urlPath);
                            $activeTheme = \App\Core\Config::get('templates.active') ?? 'default';
                            $themeUploadDir = $baseDir . '/public/uploads/themes/' . $activeTheme;
                            if (is_dir($themeUploadDir)) {
                                if (file_exists($themeUploadDir . '/' . $basename)) {
                                    $match = $themeUploadDir . '/' . $basename;
                                    $matchForward = str_replace('\\', '/', $match);
                                    $baseDirForward = str_replace('\\', '/', $baseDir);
                                    $relUrl = str_replace($baseDirForward, '', $matchForward);
                                    $newSrcFinal = rtrim(\App\Core\Config::get('app.base_path') ?? '', '/') . $relUrl;
                                    $webpPathFinal = $match;
                                } else {
                                    foreach (glob($themeUploadDir . '/' . $filename . '_*.webp') ?: [] as $match) {
                                        // Skip responsive variants
                                        if (str_ends_with($match, '_480w.webp') || str_ends_with($match, '_768w.webp')) {
                                            continue;
                                        }
                                        $matchForward = str_replace('\\', '/', $match);
                                        $baseDirForward = str_replace('\\', '/', $baseDir);
                                        $relUrl = str_replace($baseDirForward, '', $matchForward);
                                        $newSrcFinal = rtrim(\App\Core\Config::get('app.base_path') ?? '', '/') . $relUrl;
                                        $webpPathFinal = $match;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($webpPathFinal && $newSrcFinal) {
                            $attrs = str_replace($srcMatch[0], 'src="' . $newSrcFinal . '"', $attrs);
                            
                            $dir = dirname($webpPathFinal);
                            $filename = pathinfo($webpPathFinal, PATHINFO_FILENAME);
                            $ext = pathinfo($webpPathFinal, PATHINFO_EXTENSION);
                            
                            $srcsets = [];
                            if (file_exists($dir . '/' . $filename . '_480w.' . $ext)) {
                                $srcsets[] = dirname($newSrcFinal) . '/' . $filename . '_480w.' . $ext . ' 480w';
                            }
                            if (file_exists($dir . '/' . $filename . '_768w.' . $ext)) {
                                $srcsets[] = dirname($newSrcFinal) . '/' . $filename . '_768w.' . $ext . ' 768w';
                            }
                            if (!empty($srcsets)) {
                                $srcsets[] = $newSrcFinal . ' 1200w';
                                if (stripos($attrs, 'srcset=') === false) {
                                    $attrs .= ' srcset="' . implode(', ', $srcsets) . '" sizes="(max-width: 480px) 480px, (max-width: 768px) 768px, 100vw"';
                                }
                            }
                        }
                    }
                }
            }

            // Varsa lazy dokunma
            if (stripos($attrs, 'loading=') === false) {
                $attrs .= ' loading="lazy"';
            }
            if (stripos($attrs, 'decoding=') === false) {
                $attrs .= ' decoding="async"';
            }

            // width ve height yoksa tahmin et/bul
            if (stripos($attrs, 'width=') === false && stripos($attrs, 'height=') === false) {
                if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                    $src = $srcMatch[1];
                    if (str_starts_with($src, '/')) {
                        $urlPath = parse_url($src, PHP_URL_PATH);
                        $basePathStr = \App\Core\Config::get('app.base_path') ?? '';
                        if (!empty($basePathStr) && str_starts_with($urlPath, $basePathStr)) {
                            $urlPath = substr($urlPath, strlen($basePathStr));
                        }
                        $physicalPath = $baseDir . $urlPath;
                        if (file_exists($physicalPath)) {
                            $dims = @getimagesize($physicalPath);
                            if ($dims) {
                                $attrs .= ' width="' . $dims[0] . '" height="' . $dims[1] . '"';
                            }
                        }
                    }
                }
            }

            return '<img ' . $attrs . '>';
        }, $html);

        // 2. <script> etiketlerine defer ekle (inline veya defer/async/tailwindcss olmayanlar)
        $html = preg_replace_callback('/<script\s+([^>]+)>/i', function ($matches) {
            $attrs = $matches[1];

            // Eğer src varsa, defer/async yoksa ve tailwind CDN değilse defer ekle
            if (stripos($attrs, 'src=') !== false && 
                stripos($attrs, 'defer') === false && 
                stripos($attrs, 'async') === false &&
                stripos($attrs, 'tailwindcss') === false) {
                $attrs .= ' defer';
            }

            return '<script ' . $attrs . '>';
        }, $html);

        // 3. url('/assets/...') içindeki background resimlerini optimize et
        $html = preg_replace_callback('/url\([\'"]?([^\'"\)]+)[\'"]?\)/i', function ($matches) {
            $src = $matches[1];
            $baseDir = rtrim(GROO_BASE_PATH, '/\\');
            
            if (str_starts_with($src, '/') && (str_ends_with(strtolower($src), '.png') || str_ends_with(strtolower($src), '.jpg') || str_ends_with(strtolower($src), '.jpeg'))) {
                $urlPath = parse_url($src, PHP_URL_PATH);
                if ($urlPath) {
                    $basePathStr = \App\Core\Config::get('app.base_path') ?? '';
                    if (!empty($basePathStr) && str_starts_with($urlPath, $basePathStr)) {
                        $urlPath = substr($urlPath, strlen($basePathStr));
                    }
                    $physicalPath = $baseDir . $urlPath;
                    $webpPath = $physicalPath . '.webp';
                    if (file_exists($webpPath)) {
                        $newSrc = $src . '.webp';
                        return 'url(\'' . $newSrc . '\')';
                    } else {
                        $filename = pathinfo($urlPath, PATHINFO_FILENAME);
                        $activeTheme = \App\Core\Config::get('templates.active') ?? 'default';
                        $themeUploadDir = $baseDir . '/public/uploads/themes/' . $activeTheme;
                        if (is_dir($themeUploadDir)) {
                            foreach (glob($themeUploadDir . '/' . $filename . '_*.webp') ?: [] as $match) {
                                if (basename($match) !== $filename . '.webp' && basename($match) !== $filename . '.png.webp' && basename($match) !== $filename . '.jpg.webp' && basename($match) !== $filename . '.jpeg.webp') {
                                    continue;
                                }
                                $matchForward = str_replace('\\', '/', $match);
                                $baseDirForward = str_replace('\\', '/', $baseDir);
                                $relUrl = str_replace($baseDirForward, '', $matchForward);
                                $newSrc = rtrim(\App\Core\Config::get('app.base_path') ?? '', '/') . $relUrl;
                                return 'url(\'' . $newSrc . '\')';
                            }
                        }
                    }
                }
            }
            return $matches[0];
        }, $html);

        return $html;
    }
}
