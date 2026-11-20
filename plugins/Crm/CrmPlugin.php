<?php

declare(strict_types=1);

namespace Plugins\Crm;

use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenuEvent;

final class CrmPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'CRM & Ön Muhasebe',
        'slug' => 'crm',
        'version' => '1.0.0',
        'description' => 'Müşteri (Lead/B2B/B2C), zaman tüneli, görev & ajanda, ön muhasebe, ek süre/saat faturası ve bildirimler.',
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
            'title' => 'Müşteriler (CRM)',
            'route' => '/admin/crm/contacts',
            'icon' => 'icon-users',
            'priority' => 15,
        ]);

        $this->events()->dispatch(AdminMenuEvent::REGISTER, [
            'title' => 'Görevler & Ajanda',
            'route' => '/admin/crm/tasks',
            'icon' => 'icon-calendar',
            'priority' => 16,
        ]);

        $this->events()->dispatch(AdminMenuEvent::REGISTER, [
            'title' => 'Ön Muhasebe',
            'route' => '/admin/crm/finance',
            'icon' => 'icon-card',
            'priority' => 17,
        ]);
    }
}
