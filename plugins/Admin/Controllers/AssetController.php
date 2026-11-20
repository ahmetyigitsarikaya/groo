<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\Exceptions\NotFoundException;

final class AssetController
{
    private const MIME_TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    public function serve(array $params): void
    {
        $pluginDir = dirname(__DIR__);
        $root = realpath($pluginDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets');

        if ($root === false) {
            throw new NotFoundException('Admin assets are not available.');
        }

        $clean = str_replace(['..', "\0"], '', ltrim(str_replace('\\', '/', (string) ($params['file'] ?? '')), '/'));
        $target = realpath($root . DIRECTORY_SEPARATOR . $clean);

        if ($target === false || strpos($target, $root) !== 0 || !is_file($target)) {
            throw new NotFoundException('Asset not found.');
        }

        $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $mime = self::MIME_TYPES[$extension] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($target));
        header('Cache-Control: public, max-age=3600');
        readfile($target);
        exit;
    }
}
