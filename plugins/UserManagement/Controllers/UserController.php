<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Controllers;

use App\Core\Hook;
use Plugins\Admin\Support\Flash;
use Plugins\UserManagement\Repositories\ApiKeyRepository;
use Plugins\UserManagement\Repositories\RoleRepository;
use Plugins\UserManagement\Repositories\UserRepository;
use Throwable;

/**
 * UserController — Kullanıcı listesi, oluşturma, düzenleme,
 * banlama, silme ve profil sekmeleri (Hook sistemi ile).
 */
final class UserController extends BaseController
{
    private UserRepository $users;
    private RoleRepository $roles;
    private ApiKeyRepository $apiKeys;

    public function __construct()
    {
        parent::__construct();
        $this->users   = new UserRepository();
        $this->roles   = new RoleRepository();
        $this->apiKeys = new ApiKeyRepository();
    }

    // ----------------------------------------------------------------
    //  Liste
    // ----------------------------------------------------------------

    public function index(array $params): void
    {
        $this->requireAuth();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $users = $this->users->all($perPage, $offset);
        $total = $this->users->count();

        $this->render('pages/usermanagement/users-index', [
            'users'        => $users,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => (int) ceil($total / $perPage),
            'stats'        => [
                'active' => $this->users->countByStatus('active'),
                'banned' => $this->users->countByStatus('banned'),
            ],
        ]);
    }

    // ----------------------------------------------------------------
    //  Oluştur
    // ----------------------------------------------------------------

    public function create(array $params): void
    {
        $this->requireAuth();

        $this->render('pages/usermanagement/user-form', [
            'user'       => null,
            'user_roles' => [],
            'all_roles'  => $this->roles->all(),
            'api_keys'   => [],
            'profile_tabs' => [],
        ]);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $username = $this->input('username');
        $email    = $this->input('email');
        $password = $this->input('password');
        $status   = $this->input('status', 'active');
        $roleIds  = array_map('intval', (array) ($_POST['role_ids'] ?? []));

        try {
            if ($username === '' || $email === '' || $password === '') {
                throw new \RuntimeException('Kullanıcı adı, e-posta ve şifre zorunludur.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Geçerli bir e-posta adresi girin.');
            }
            if (strlen($password) < 6) {
                throw new \RuntimeException('Şifre en az 6 karakter olmalıdır.');
            }

            $id = $this->users->create($username, $email, $password, $status);
            $this->users->syncRoles($id, $roleIds);

            Hook::doAction('user.created', ['id' => $id, 'username' => $username, 'email' => $email]);

            Flash::set('success', 'Kullanıcı başarıyla oluşturuldu.');
            $this->redirect('/admin/users');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/users/new');
        }
    }

    // ----------------------------------------------------------------
    //  Düzenle / Güncelle
    // ----------------------------------------------------------------

    public function edit(array $params): void
    {
        $this->requireAuth();

        $user = $this->users->find((int) $params['id']);
        if ($user === null) {
            Flash::set('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
        }

        $userId = (int) $user['id'];

        // Profil sekmeleri — diğer eklentiler Hook ile sekme ekleyebilir
        $tabs = Hook::applyFilter('user_profile_tabs', [], $userId);

        $this->render('pages/usermanagement/user-form', [
            'user'         => $user,
            'user_roles'   => $this->users->roles($userId),
            'all_roles'    => $this->roles->all(),
            'api_keys'     => $this->apiKeys->forUser($userId),
            'profile_tabs' => $tabs,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id   = (int) $params['id'];
        $user = $this->users->find($id);
        if ($user === null) {
            Flash::set('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
        }

        $data = [
            'username' => $this->input('username', (string) $user['username']),
            'email'    => $this->input('email',    (string) $user['email']),
            'status'   => $this->input('status',   (string) $user['status']),
        ];

        $password = $this->input('password');
        if ($password !== '') {
            if (strlen($password) < 6) {
                Flash::set('error', 'Şifre en az 6 karakter olmalıdır.');
                $this->redirect('/admin/users/' . $id . '/edit');
            }
            $data['password'] = $password;
        }

        $roleIds = array_map('intval', (array) ($_POST['role_ids'] ?? []));

        try {
            $this->users->update($id, $data);
            $this->users->syncRoles($id, $roleIds);

            Hook::doAction('user.updated', array_merge(['id' => $id], $data));

            Flash::set('success', 'Kullanıcı güncellendi.');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/users/' . $id . '/edit');
    }

    // ----------------------------------------------------------------
    //  Ban / Unban
    // ----------------------------------------------------------------

    public function ban(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int) $params['id'];
        if ($this->users->ban($id)) {
            Hook::doAction('user.banned', $id);
            Flash::set('success', 'Kullanıcı yasaklandı.');
        } else {
            Flash::set('error', 'İşlem başarısız.');
        }
        $this->redirect('/admin/users');
    }

    public function unban(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int) $params['id'];
        if ($this->users->unban($id)) {
            Hook::doAction('user.unbanned', $id);
            Flash::set('success', 'Kullanıcının yasağı kaldırıldı.');
        } else {
            Flash::set('error', 'İşlem başarısız.');
        }
        $this->redirect('/admin/users');
    }

    // ----------------------------------------------------------------
    //  Sil
    // ----------------------------------------------------------------

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int) $params['id'];
        if ($this->users->delete($id)) {
            Hook::doAction('user.deleted', $id);
            Flash::set('success', 'Kullanıcı silindi.');
        } else {
            Flash::set('error', 'Kullanıcı silinemedi.');
        }
        $this->redirect('/admin/users');
    }
}
