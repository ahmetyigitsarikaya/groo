<?php

declare(strict_types=1);

namespace Plugins\Content\Controllers;

use App\Core\Http\ApiResponse;
use Plugins\Content\Services\PostService;

final class ApiPostController
{
    private PostService $postService;

    public function __construct(?PostService $postService = null)
    {
        $this->postService = $postService ?? new PostService();
    }

    public function index(array $params = []): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

        $posts = $this->postService->getPublishedPosts($limit, $page);

        ApiResponse::success([
            'posts' => $posts,
            'page' => max(1, $page),
            'limit' => max(1, $limit),
        ], 'Yayınlanmış yazılar getirildi.')->send();
    }

    public function show(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $post = $this->postService->getBySlug($slug);

        if ($post === null) {
            ApiResponse::error('İçerik bulunamadı.', 404)->send();
        }

        ApiResponse::success([
            'post' => $post,
        ], 'İçerik detayı getirildi.')->send();
    }
}
