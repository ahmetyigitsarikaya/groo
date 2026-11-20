<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Groo',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'base_path' => isset($_ENV['APP_BASE_PATH']) && $_ENV['APP_BASE_PATH'] !== '' ? $_ENV['APP_BASE_PATH'] : null,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'auto_migrate' => filter_var($_ENV['AUTO_MIGRATE'] ?? false, FILTER_VALIDATE_BOOLEAN),
];
