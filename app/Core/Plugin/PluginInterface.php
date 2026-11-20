<?php

declare(strict_types=1);

namespace App\Core\Plugin;

interface PluginInterface
{
    public function register(): void;

    public function boot(): void;

    public function getMetadata(): array;
}
