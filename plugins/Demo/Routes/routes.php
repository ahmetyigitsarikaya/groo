<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Demo\Controllers\DemoController;

/** @var Router $router */
$router->get('/hello', [DemoController::class, 'hello']);
$router->get('/hello/{name}', [DemoController::class, 'hello']);
$router->get('/entities', [DemoController::class, 'entities']);
$router->get('/records/{slug}', [DemoController::class, 'records']);
$router->post('/records/{slug}', [DemoController::class, 'createRecord']);
$router->get('/records/{slug}/search/{attribute}', [DemoController::class, 'searchByAttribute']);
