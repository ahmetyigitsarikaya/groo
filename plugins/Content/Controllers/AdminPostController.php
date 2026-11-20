<?php

declare(strict_types=1);

namespace Plugins\Content\Controllers;

use App\Core\Exceptions\ValidationException;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;
use Plugins\Content\Services\PostService;
use Throwable;

final class AdminPostController extends BaseController
{
    private PostService $postService;

    public function __construct(?PostService $postService = null)
    {
        parent::__construct();
        $this->postService = $postService ?? new PostService();
    }

    public function index(array $params = []): void
    {
        $posts = $this->postService->getAllPosts();

        $this->render('admin/posts/index', [
            'posts' => $posts,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->render('admin/posts/form', [
            'post' => null,
            'is_edit' => false,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireCsrf();

        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
            'banner_image' => trim((string) ($_POST['banner_image'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'draft'),
            'published_at' => trim((string) ($_POST['published_at'] ?? '')),
            'content_blocks' => $_POST['content_blocks'] ?? [],
        ];

        try {
            $this->postService->createPost($data);
            Flash::set('success', 'Yazı başarıyla oluşturuldu.');
            \App\Core\Middleware\PageCache::flush();
            $this->redirect('/admin/posts');
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $this->render('admin/posts/form', [
                'post' => $data,
                'is_edit' => false,
            ]);
        } catch (Throwable $e) {
            Flash::set('error', 'Hata oluştu: ' . $e->getMessage());
            $this->render('admin/posts/form', [
                'post' => $data,
                'is_edit' => false,
            ]);
        }
    }

    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $post = $this->postService->getPostById($id);

        if ($post === null) {
            Flash::set('error', 'Yazı bulunamadı.');
            $this->redirect('/admin/posts');
        }

        $this->render('admin/posts/form', [
            'post' => $post,
            'is_edit' => true,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $post = $this->postService->getPostById($id);

        if ($post === null) {
            Flash::set('error', 'Yazı bulunamadı.');
            $this->redirect('/admin/posts');
        }

        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
            'banner_image' => trim((string) ($_POST['banner_image'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'draft'),
            'published_at' => trim((string) ($_POST['published_at'] ?? '')),
            'content_blocks' => $_POST['content_blocks'] ?? [],
        ];

        try {
            $this->postService->updatePost($id, $data);
            Flash::set('success', 'Yazı başarıyla güncellendi.');
            \App\Core\Middleware\PageCache::flush();
            $this->redirect('/admin/posts');
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $data['id'] = $id;
            $this->render('admin/posts/form', [
                'post' => $data,
                'is_edit' => true,
            ]);
        } catch (Throwable $e) {
            Flash::set('error', 'Hata oluştu: ' . $e->getMessage());
            $data['id'] = $id;
            $this->render('admin/posts/form', [
                'post' => $data,
                'is_edit' => true,
            ]);
        }
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $success = $this->postService->deletePost($id);

        if ($success) {
            Flash::set('success', 'Yazı silindi.');
            \App\Core\Middleware\PageCache::flush();
        } else {
            Flash::set('error', 'Yazı silinemedi.');
        }

        $this->redirect('/admin/posts');
    }
}
