<?php

declare(strict_types=1);

namespace Plugins\Admin\Support;

final class AdminMenuEvent
{
    public const REGISTER = 'admin.menu.register';

    public const COLLECT = 'admin.menu.collect';

    public const ADDED = 'admin.menu.added';
}
