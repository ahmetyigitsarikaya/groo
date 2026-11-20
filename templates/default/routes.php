<?php
declare(strict_types=1);
use App\Core\Http\Router;
/** @var Router $router */
$router->get('/', static function (array $params): void {
    require __DIR__ . '/index.php';
});
