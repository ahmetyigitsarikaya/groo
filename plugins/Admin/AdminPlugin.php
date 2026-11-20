<?php

declare(strict_types=1);

namespace Plugins\Admin;

use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenu;
use Plugins\Admin\Support\AdminMenuEvent;
use Plugins\Admin\Support\AdminMenuRegistry;

final class AdminPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'Admin',
        'slug' => 'admin',
        'version' => '1.0.0',
        'description' => 'Groo admin paneli: kimlik dogrulama, dashboard, dinamik entity yonetimi ve eklenti sistemi.',
        'requires' => [],
        'route_prefix' => '/admin',
    ];

    public function getMetadata(): array
    {
        $meta = parent::getMetadata();
        $meta['route_prefix'] = \Plugins\Admin\Support\AdminPaths::prefix();
        return $meta;
    }

    public function register(): void
    {
        View::addPath($this->path('Views'));

        AdminMenu::add('Kontrol Paneli', '/admin', 'icon-home', 0);
        AdminMenu::add('Eklentiler ve Sablon', '/admin/plugins', 'icon-plug', 20);
        AdminMenu::add('Görünüm & Şablonlar', '/admin/themes', 'icon-layout', 21);

        $this->events()->listen(AdminMenuEvent::REGISTER, static function ($payload) {
            if (is_array($payload) && isset($payload['title'], $payload['route'])) {
                AdminMenuRegistry::instance()->add(
                    (string) $payload['title'],
                    (string) $payload['route'],
                    (string) ($payload['icon'] ?? 'icon-dot'),
                    (int) ($payload['priority'] ?? 50)
                );
            }

            return $payload;
        }, 0);
    }

    public function boot(): void
    {
        $this->events()->dispatch(AdminMenuEvent::COLLECT);
    }
}
