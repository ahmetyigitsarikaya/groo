<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Templates\DualTesisat\Controllers\BlogController;
use Templates\DualTesisat\Controllers\ContactController;
use Templates\DualTesisat\Controllers\HomeController;
use Templates\DualTesisat\Controllers\PageController;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);
$router->get('/hizmetler', [PageController::class, 'services']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/iletisim', [ContactController::class, 'show']);
$router->post('/iletisim', [ContactController::class, 'submit']);
