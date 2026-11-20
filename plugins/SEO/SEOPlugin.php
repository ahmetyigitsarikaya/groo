<?php

declare(strict_types=1);

namespace Plugins\SEO;

use App\Core\Config;
use App\Core\Plugin\AbstractPlugin;
use App\Core\View\View;
use Plugins\Admin\Support\AdminMenu;

final class SEOPlugin extends AbstractPlugin
{
    protected array $metadata = [
        'name'        => 'SEO Engine',
        'slug'        => 'seo',
        'version'     => '1.0.0',
        'description' => 'Dinamik meta etiketleri, robots.txt, sitemap.xml ve yönetilebilir Admin SEO paneli sunar.',
        'requires'    => ['admin'],
    ];

    public function getMetadata(): array
    {
        $meta = parent::getMetadata();
        $meta['route_prefix'] = \Plugins\Admin\Support\AdminPaths::path('/seo');
        return $meta;
    }

    public function register(): void
    {
        View::addPath($this->path('Views'));

        $adminPath = (string) Config::get('app.admin_path', 'admin');
        AdminMenu::add('SEO Yönetimi', '/' . $adminPath . '/seo', 'icon-search', 60, 'admin.seo');
    }

    public function boot(): void
    {
        // Front-end sitemap ve robots.txt rotalarını kaydet (Tüm pluginler yüklendikten sonra Router üzerinden)
        $this->events()->listen('app.booted', static function ($app) {
            $router = $app->getRouter();
            
            $router->get('/robots\.txt', [\Plugins\SEO\Controllers\Front\SitemapController::class, 'robots']);
            $router->get('/sitemap\.xml', [\Plugins\SEO\Controllers\Front\SitemapController::class, 'sitemap']);
        });
    }
}
