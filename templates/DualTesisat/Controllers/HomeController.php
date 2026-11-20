<?php

declare(strict_types=1);

namespace Templates\DualTesisat\Controllers;

use App\Core\View\View;

final class HomeController
{
    public function index(array $params = []): void
    {
        View::make('pages/home')->display();
    }
}
