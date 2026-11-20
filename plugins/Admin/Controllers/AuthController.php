<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\View\View;
use Plugins\Admin\Support\Csrf;

final class AuthController extends BaseController
{
    public function showLogin(array $params): void
    {
        if ($this->auth->check()) {
            $this->redirect('/admin');
        }

        View::make('pages/auth/login', [
            'csrf_field' => Csrf::field(),
            'failed' => ($_GET['hata'] ?? '') === '1',
        ])->display();
    }

    public function login(array $params): void
    {
        $this->requireCsrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

        if ($email === '' || $password === '') {
            $this->redirect('/admin/login?hata=1');
        }

        try {
            if (!$this->auth->attempt($email, $password, $remember)) {
                $this->redirect('/admin/login?hata=1');
            }
        } catch (\Exception $e) {
            \Plugins\Admin\Support\Flash::set('error', $e->getMessage());
            $this->redirect('/admin/login');
        }

        $this->redirect('/admin');
    }

    public function logout(array $params): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $this->auth->logout();
        $this->redirect('/admin/login');
    }
}
