<?php

declare(strict_types=1);

use App\Core\Http\Router;
use Plugins\Admin\Http\Middleware\AdminAuthMiddleware;
use Plugins\UserManagement\Controllers\AdminUserController;
use Plugins\UserManagement\Controllers\ApiKeyController;
use Plugins\UserManagement\Controllers\RoleController;
use Plugins\UserManagement\Controllers\UserController;

/** @var Router $router */

// ----------------------------------------------------------------
//  Admin — Kullanıcı Yönetimi (AdminAuthMiddleware korumalı)
// ----------------------------------------------------------------
$router->group('', static function (Router $router): void {

    // Kullanıcı listesi
    $router->get('/',         [UserController::class, 'index']);

    // Kullanıcı oluştur
    $router->get('/new',      [UserController::class, 'create']);
    $router->post('/new',     [UserController::class, 'store']);

    // Kullanıcı düzenle
    $router->get('/{id:\d+}/edit',   [UserController::class, 'edit']);
    $router->post('/{id:\d+}/edit',  [UserController::class, 'update']);

    // Ban / Unban / Sil
    $router->post('/{id:\d+}/ban',    [UserController::class, 'ban']);
    $router->post('/{id:\d+}/unban',  [UserController::class, 'unban']);
    $router->post('/{id:\d+}/delete', [UserController::class, 'destroy']);

    // API Anahtar Yönetimi (kullanıcı sayfasından)
    $router->post('/{user_id:\d+}/api-keys/generate',              [ApiKeyController::class, 'generate']);
    $router->post('/{user_id:\d+}/api-keys/{key_id:\d+}/revoke',   [ApiKeyController::class, 'revoke']);
    $router->post('/{user_id:\d+}/api-keys/{key_id:\d+}/delete',   [ApiKeyController::class, 'delete']);

    // Roller
    $router->get('/roles',                    [RoleController::class, 'index']);
    $router->get('/roles/new',                [RoleController::class, 'create']);
    $router->post('/roles/new',               [RoleController::class, 'store']);
    $router->get('/roles/{id:\d+}/edit',      [RoleController::class, 'edit']);
    $router->post('/roles/{id:\d+}/edit',     [RoleController::class, 'update']);
    $router->post('/roles/{id:\d+}/delete',   [RoleController::class, 'destroy']);

}, [new AdminAuthMiddleware()]);

// ----------------------------------------------------------------
//  Admin Hesap Yönetimi (admin.manage yetkisi gerekli)
// ----------------------------------------------------------------
$router->group('/admins', static function (Router $router): void {

    $router->get('/',                                [AdminUserController::class, 'index']);
    $router->get('/new',                             [AdminUserController::class, 'create']);
    $router->post('/new',                            [AdminUserController::class, 'store']);
    $router->get('/{id:\d+}/edit',                   [AdminUserController::class, 'edit']);
    $router->post('/{id:\d+}/edit',                  [AdminUserController::class, 'update']);
    $router->post('/{id:\d+}/delete',                [AdminUserController::class, 'destroy']);

}, [new AdminAuthMiddleware('admin.manage')]);

// ----------------------------------------------------------------
//  REST API — Kimlik doğrulama gerektiren public endpoint'ler
//  Prefix: /admin/users/api  (PluginManager /admin/users prefix'i ekler)
// ----------------------------------------------------------------
$router->get('/api/me', [ApiKeyController::class, 'me']);
