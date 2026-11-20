<?php

declare(strict_types=1);

namespace Plugins\Media;

use App\Core\Plugin\AbstractPlugin;

final class MediaPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name'        => 'Media Manager',
        'slug'        => 'media',
        'version'     => '1.0.0',
        'description' => 'Medya Yöneticisi ve Otomatik WebP Dönüştürücü.',
        'requires'    => ['admin']
    ];

    public function getMetadata(): array
    {
        $meta = parent::getMetadata();
        $meta['route_prefix'] = \Plugins\Admin\Support\AdminPaths::path('/media');
        return $meta;
    }

    public function register(): void
    {
        \App\Core\View\View::addPath($this->path('Views'));
    }

    public function boot(): void
    {
        $dispatcher = \App\Core\Events\EventDispatcher::instance();
        $dispatcher->listen('admin.menu.build', function (array $menu) {
            $menu[] = [
                'title' => 'Medya Kütüphanesi',
                'url'   => \Plugins\Admin\Support\AdminPaths::path('/media'),
                'icon'  => '🖼️',
                'order' => 45,
            ];
            return $menu;
        });
    }
}
