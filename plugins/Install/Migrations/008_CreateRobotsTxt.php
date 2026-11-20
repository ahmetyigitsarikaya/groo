<?php
declare(strict_types=1);

namespace Plugins\Install\Migrations;

return new class {
    public function up(): void
    {
        $basePath = dirname(__DIR__, 3); // Project root directory
        $publicRobotsPath = $basePath . '/public/robots.txt';
        $rootRobotsPath = $basePath . '/robots.txt';
        
        // Define default robots.txt content
        $defaultRobotsTxt = "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nAllow: /\n\nSitemap: /sitemap.xml\n";

        // Write to public folder if it exists, otherwise write to root
        if (is_dir($basePath . '/public')) {
            if (!file_exists($publicRobotsPath)) {
                file_put_contents($publicRobotsPath, $defaultRobotsTxt);
            }
        } else {
            if (!file_exists($rootRobotsPath)) {
                file_put_contents($rootRobotsPath, $defaultRobotsTxt);
            }
        }
    }

    public function down(): void
    {
        // Typically migrations down() don't delete user files like robots.txt to be safe.
    }
};
