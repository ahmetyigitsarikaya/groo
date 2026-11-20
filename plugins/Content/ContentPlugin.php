<?php

declare(strict_types=1);

namespace Plugins\Content;

use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenuEvent;

final class ContentPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'Content',
        'slug' => 'content',
        'version' => '1.0.0',
        'description' => 'Headless uyumlu icerik yonetimi (CMS/Posts) eklentisi. API ve template entegrasyonu sunar.',
        'requires' => [],
        'route_prefix' => '',
    ];

    public function register(): void
    {
        View::addPath($this->path('Views'));
    }

    public function boot(): void
    {
        $this->events()->dispatch(AdminMenuEvent::REGISTER, [
            'title' => 'Yazılar',
            'route' => '/admin/posts',
            'icon' => 'icon-doc',
            'priority' => 40,
        ]);
    }
}
