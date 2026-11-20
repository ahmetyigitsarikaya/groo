<?php

declare(strict_types=1);

$active = trim((string) ($_ENV['ACTIVE_PLUGINS'] ?? ''));

return [
    'active' => $active === '' ? null : array_values(array_filter(array_map('trim', explode(',', $active)))),
];
