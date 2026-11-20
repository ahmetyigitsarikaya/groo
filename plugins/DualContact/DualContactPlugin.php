<?php

declare(strict_types=1);

namespace Plugins\DualContact;

use App\Core\Http\Router;
use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenu;

final class DualContactPlugin extends AbstractPlugin
{
    public function register(): void
    {
        View::addPath($this->path('Views'));
        
        // Add Admin Menu Group
        AdminMenu::add('Gelen Mesajlar', '/admin/contact-messages', 'icon-mail', 35, '');
    }

    public function boot(): void
    {
        // No boot logic needed for now.
    }

    public function getMetadata(): array
    {
        return [
            'name' => 'DualContact',
            'route_prefix' => ''
        ];
    }
}
