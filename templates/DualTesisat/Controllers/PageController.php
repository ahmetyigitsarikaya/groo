<?php

declare(strict_types=1);

namespace Templates\DualTesisat\Controllers;

use App\Core\View\View;

final class PageController
{
    public function services(array $params = []): void
    {
        View::make('pages/services')->display();
    }
}
