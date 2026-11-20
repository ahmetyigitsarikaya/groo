<?php

declare(strict_types=1);

namespace Plugins\Demo;

use App\Core\Plugin\AbstractPlugin;

final class DemoPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'Demo',
        'slug' => 'demo',
        'version' => '1.0.0',
        'description' => 'Sample plugin proving the Groo plugin architecture works.',
        'requires' => [],
        'route_prefix' => '/demo',
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->events()->listen('demo.greeting', static function ($payload): string {
            $name = is_string($payload) && $payload !== '' ? $payload : 'World';

            return sprintf('Hello, %s! (served by the Demo plugin)', $name);
        }, 10);

        $this->events()->listen('app.booted', static function (): void {
            error_log('[Groo][Demo] Demo plugin booted.');
        }, 0);

        $this->events()->dispatch('admin.menu.register', [
            'title' => 'Demo Kayıtları',
            'route' => '/admin/entities/contact',
            'icon' => 'icon-mail',
            'priority' => 60,
        ]);
    }
}
