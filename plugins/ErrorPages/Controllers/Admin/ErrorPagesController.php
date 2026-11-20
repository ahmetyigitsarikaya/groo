<?php

declare(strict_types=1);

namespace Plugins\ErrorPages\Controllers\Admin;

use App\Core\Database\Connection;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;

final class ErrorPagesController extends BaseController
{
    public function index(array $params = []): void
    {
        if (!$this->auth->can('admin.error_pages') && !$this->auth->can('*')) {
            $this->redirect('/admin');
        }

        $pdo = Connection::getInstance();
        $pages = $pdo->query("SELECT * FROM error_pages ORDER BY error_code ASC")->fetchAll();

        $this->render('admin-error-pages', [
            'pages' => $pages
        ]);
    }

    public function save(array $params = []): void
    {
        if (!$this->auth->can('admin.error_pages') && !$this->auth->can('*')) {
            $this->redirect('/admin');
        }
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $errorCode = (int) ($_POST['error_code'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $isActive = (int) ($_POST['is_active'] ?? 0);

        if ($errorCode <= 0 || $title === '') {
            Flash::set('error', 'Hata kodu ve başlık zorunludur.');
            $this->redirect('/admin/error-pages');
        }

        $pdo = Connection::getInstance();

        if ($id > 0) {
            // Check if error code is being duplicated
            $stmt = $pdo->prepare("SELECT id FROM error_pages WHERE error_code = ? AND id != ?");
            $stmt->execute([$errorCode, $id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                Flash::set('error', 'Bu hata kodu için zaten bir sayfa var.');
                $this->redirect('/admin/error-pages');
            }

            $stmt = $pdo->prepare("UPDATE error_pages SET error_code = ?, title = ?, message = ?, image_url = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$errorCode, $title, $message, $imageUrl, $isActive, $id]);
            $msg = "Hata sayfası başarıyla güncellendi.";
        } else {
            // Check duplicate
            $stmt = $pdo->prepare("SELECT id FROM error_pages WHERE error_code = ?");
            $stmt->execute([$errorCode]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                Flash::set('error', 'Bu hata kodu için zaten bir sayfa var.');
                $this->redirect('/admin/error-pages');
            }

            $stmt = $pdo->prepare("INSERT INTO error_pages (error_code, title, message, image_url, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$errorCode, $title, $message, $imageUrl, $isActive]);
            $msg = "Yeni hata sayfası eklendi.";
        }

        Flash::set('success', $msg);
        $this->redirect('/admin/error-pages');
    }
}
