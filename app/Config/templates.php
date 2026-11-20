<?php

declare(strict_types=1);

return [
    'active' => isset($_ENV['ACTIVE_TEMPLATE']) && trim((string) $_ENV['ACTIVE_TEMPLATE']) !== ''
        ? trim((string) $_ENV['ACTIVE_TEMPLATE'])
        : null,
];
