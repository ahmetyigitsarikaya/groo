<?php

declare(strict_types=1);

namespace App\Core\Template;

interface TemplateInterface
{
    public function register(): void;

    public function boot(): void;

    public function getMetadata(): array;

    public function getRequiredPlugins(): array;
}
