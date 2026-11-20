<?php

declare(strict_types=1);

define('GROO_BASE_PATH', dirname(__DIR__));

require GROO_BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\Core\Application;
use App\Core\Config;
use App\Core\Exceptions\Handler;

Config::load(GROO_BASE_PATH);

$grooInstalled = $_ENV['APP_INSTALLED'] ?? 'false';
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$grooBase = rtrim((string) ($_ENV['APP_BASE_PATH'] ?? ''), '/');
$installUri = $grooBase . '/install';

if (!filter_var($grooInstalled, FILTER_VALIDATE_BOOLEAN) && strpos((string)$requestUri, $installUri) !== 0) {
    header('Location: ' . $installUri, true, 302);
    exit;
}

Handler::register();

$app = Application::create(GROO_BASE_PATH);
$app->boot();
$app->run();
