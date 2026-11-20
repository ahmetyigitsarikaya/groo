<?php

declare(strict_types=1);

namespace Plugins\DualContact\Controllers\Admin;

use App\Core\Database\Connection;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;

final class ContactMessagesController extends BaseController
{
    public function index(array $params = []): void
    {
        $pdo = Connection::getInstance();
        $messages = $pdo->query("SELECT * FROM dual_contact_messages ORDER BY created_at DESC")->fetchAll();

        $this->render('admin-messages', [
            'messages' => $messages
        ]);
    }

    public function markAsRead(array $params = []): void
    {
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare("UPDATE dual_contact_messages SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
        }

        Flash::set('success', 'Mesaj okundu olarak işaretlendi.');
        $this->redirect('/admin/contact-messages');
    }

    public function delete(array $params = []): void
    {
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare("DELETE FROM dual_contact_messages WHERE id = ?");
            $stmt->execute([$id]);
        }

        Flash::set('success', 'Mesaj başarıyla silindi.');
        $this->redirect('/admin/contact-messages');
    }
}
