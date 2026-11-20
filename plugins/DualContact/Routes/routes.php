<?php

declare(strict_types=1);

/** @var \App\Core\Http\Router $router */

$router->group('/admin/contact-messages', function ($router) {
    $router->get('/', 'Plugins\DualContact\Controllers\Admin\ContactMessagesController@index');
    $router->post('/read', 'Plugins\DualContact\Controllers\Admin\ContactMessagesController@markAsRead');
    $router->post('/delete', 'Plugins\DualContact\Controllers\Admin\ContactMessagesController@delete');
});
