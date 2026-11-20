<?php

declare(strict_types=1);

namespace Plugins\Install;

use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;

final class InstallPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name' => 'Install Manager',
        'slug' => 'install',
        'version' => '1.0.0',
        'description' => 'Sistem kurulumu ve veritabanı migrasyonlarının merkezileştirilmiş yöneticisi.',
    ];

    public function boot(): void
    {
        // No boot logic needed for installer
    }

    public function register(): void
    {
        View::addPath($this->path('Views'));
    }

    public function registerRoutes(\App\Core\Http\Router $router): void
    {
        $router->group('/system-install', function (\App\Core\Http\Router $router) {
            $router->get('/', 'Plugins\Install\Controllers\InstallController@index');
            $router->post('/', 'Plugins\Install\Controllers\InstallController@run');
        });
    }
}
