<?php

declare(strict_types=1);

namespace Templates\DualTesisat\Controllers;

use App\Core\Config;
use App\Core\View\View;
use Plugins\Content\Support\ContentHelper;
use Throwable;

final class BlogController
{
    public function index(array $params = []): void
    {
        try {
            // Using the Content plugin's helper to get published posts
            $posts = ContentHelper::getPosts(10);
        } catch (Throwable $e) {
            $posts = [];
        }

        View::make('pages/blog', [
            'posts' => $posts,
        ])->display();
    }

    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';
        
        try {
            $post = ContentHelper::getPost($slug);
        } catch (Throwable $e) {
            $post = null;
        }

        if (!$post) {
            // We could trigger 404 here, but simple redirect for now
            header('Location: ' . Config::get('app.base_path', '') . '/blog', true, 303);
            exit;
        }

        View::make('pages/post', [
            'post' => $post,
        ])->display();
    }
}
