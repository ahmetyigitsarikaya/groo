<?php

declare(strict_types=1);

namespace Plugins\UserManagement;

use App\Core\Hook;
use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenu;
use Plugins\Admin\Support\AdminMenuEvent;
use Plugins\Admin\Support\AdminMenuRegistry;
use Plugins\Admin\Support\AdminPaths;

final class UserManagementPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name'         => 'UserManagement',
        'slug'         => 'usermanagement',
        'version'      => '1.0.0',
        'description'  => 'Discord tarzı rol yönetimi, gelişmiş kullanıcı yönetimi, API anahtar altyapısı ve genişletilebilir Hook sistemi.',
        'requires'     => ['admin'],
        'route_prefix' => '/admin/users',
    ];

    public function getMetadata(): array
    {
        $meta = parent::getMetadata();
        $meta['route_prefix'] = \Plugins\Admin\Support\AdminPaths::path('/users');
        return $meta;
    }

    public function register(): void
    {
        // Plugin'in Views klasörünü View engine'e ekle
        View::addPath($this->path('Views'));

        // Admin menüsüne eklentinin sayfalarını kaydet
        AdminMenu::add('Kullanıcılar',    AdminPaths::path('/users'),          'icon-users',  10, 'admin.users');
        AdminMenu::add('Roller',           AdminPaths::path('/users/roles'),    'icon-shield', 11, 'admin.roles');
        AdminMenu::add('Admin Hesapları',  AdminPaths::path('/users/admins'),   'icon-lock',   12, 'admin.manage');

        // Diğer eklentilerin menü eklemesi için event dispatch
        $this->events()->listen(AdminMenuEvent::REGISTER, static function ($payload) {
            if (is_array($payload) && isset($payload['title'], $payload['route'])) {
                AdminMenuRegistry::instance()->add(
                    (string) $payload['title'],
                    (string) $payload['route'],
                    (string) ($payload['icon'] ?? 'icon-dot'),
                    (int)    ($payload['priority'] ?? 50)
                );
            }
            return $payload;
        }, 0);

        // Hook sistemi: varsayılan profil sekmeleri
        Hook::filter('user_profile_tabs', static function (array $tabs, int $userId): array {
            // Temel "Genel Bilgiler" sekmesi her zaman burada
            array_unshift($tabs, [
                'id'    => 'general',
                'label' => 'Genel Bilgiler',
                'icon'  => '👤',
            ]);
            return $tabs;
        }, 1);
    }

    public function boot(): void
    {
        $this->events()->dispatch(AdminMenuEvent::COLLECT);
    }
}
