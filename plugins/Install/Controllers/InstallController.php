<?php

declare(strict_types=1);

namespace Plugins\Install\Controllers;

use App\Core\Config;
use App\Core\View\View;

final class InstallController
{
    public function index(): void
    {
        View::make('install', [])->display();
    }

    public function run(): void
    {
        $log = [];
        try {
            $migrationsDir = __DIR__ . '/../Migrations';
            if (is_dir($migrationsDir)) {
                $files = glob($migrationsDir . '/*.php');
                sort($files);

                foreach ($files as $file) {
                    $migration = require $file;
                    if (is_object($migration) && method_exists($migration, 'up')) {
                        $migration->up();
                        $log[] = "Executed: " . basename($file);
                    }
                }
            }
            $success = true;
        } catch (\Throwable $e) {
            $success = false;
            $log[] = "Error: " . $e->getMessage();
            
            // Debug bilgisi
            $log[] = "DEBUG DSN: mysql:host=" . Config::get('db.host') . ";dbname=" . Config::get('db.database');
            $log[] = "DEBUG USER: " . Config::get('db.username');
        }

        View::make('install', [
            'success' => $success,
            'log' => $log,
            'done' => true
        ])->display();
    }
}
