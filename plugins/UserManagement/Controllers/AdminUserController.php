<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Controllers;

use App\Core\Database\QueryBuilder;
use Plugins\Admin\Support\Flash;
use Plugins\UserManagement\Repositories\RoleRepository;
use Plugins\UserManagement\Repositories\UserRepository;
use Throwable;

/**
 * AdminUserController — Artık um_users tablosunu kullanır.
 *
 * "Admin hesabı" = en az bir um_role ile admin.* yetkisi olan kullanıcı.
 * Bu sayfadan kullanıcılara admin rolü atanabilir/kaldırılabilir.
 * Yalnızca 'admin.manage' yetkisine sahip kullanıcılar erişebilir.
 */
final class AdminUserController extends BaseController
{
    private UserRepository $users;
    private RoleRepository $roles;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
        $this->roles = new RoleRepository();
    }

    // ----------------------------------------------------------------
    //  Admin yetkili kullanıcılar listesi
    // ----------------------------------------------------------------

    public function index(array $params): void
    {
        $this->requireAuth();

        // Admin yetkisi olan kullanıcıları bul:
        // admin.* içeren rollere sahip kullanıcılar
        $adminRoleIds = $this->getAdminRoleIds();

        if ($adminRoleIds === []) {
            $adminUsers = [];
        } else {
            // um_user_roles üzerinden admin rolü olanları çek
            $pivotRows = QueryBuilder::table('um_user_roles')
                ->select('user_id')
                ->whereIn('role_id', $adminRoleIds)
                ->get();

            $adminUserIds = array_unique(array_column($pivotRows, 'user_id'));

            if ($adminUserIds === []) {
                $adminUsers = [];
            } else {
                $adminUsers = QueryBuilder::table('um_users')
                    ->whereIn('id', $adminUserIds)
                    ->orderBy('created_at')
                    ->get();
            }
        }

        // Her kullanıcı için roller
        $adminUsers = array_map(function (array $u): array {
            $u['um_roles'] = $this->users->roles((int) $u['id']);
            return $u;
        }, $adminUsers);

        $this->render('pages/usermanagement/admin-users-index', [
            'admins'    => $adminUsers,
            'all_roles' => $this->roles->all(),
        ]);
    }

    // ----------------------------------------------------------------
    //  Kullanıcıya admin rolü ver / yönet (edit formu)
    // ----------------------------------------------------------------

    public function create(array $params): void
    {
        $this->requireAuth();

        // Tüm kullanıcıları listele — birini admin yapmak için
        $allUsers = $this->users->all(200, 0);

        $this->render('pages/usermanagement/admin-user-form', [
            'admin'       => null,
            'admin_roles' => [],
            'all_roles'   => $this->roles->all(),
            'all_users'   => $allUsers,
        ]);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $userId  = $this->inputInt('user_id');
        $roleIds = array_map('intval', (array) ($_POST['role_ids'] ?? []));

        if ($userId === 0) {
            Flash::set('error', 'Kullanıcı seçin.');
            $this->redirect('/admin/users/admins/new');
        }

        $user = $this->users->find($userId);
        if ($user === null) {
            Flash::set('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users/admins/new');
        }

        try {
            $this->users->syncRoles($userId, $roleIds);
            Flash::set('success', '"' . ($user['username'] ?? '') . '" kullanıcısına roller atandı.');
            $this->redirect('/admin/users/admins');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/users/admins/new');
        }
    }

    public function edit(array $params): void
    {
        $this->requireAuth();

        $user = $this->users->find((int) $params['id']);
        if ($user === null) {
            Flash::set('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users/admins');
        }

        $this->render('pages/usermanagement/admin-user-form', [
            'admin'       => $user,
            'admin_roles' => $this->users->roles((int) $user['id']),
            'all_roles'   => $this->roles->all(),
            'all_users'   => [],
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
            $this->redirect('/admin/users/admins');
        }

        $data    = [];
        $password = $this->input('password');
        if ($password !== '') {
            if (strlen($password) < 6) {
                Flash::set('error', 'Şifre en az 6 karakter olmalıdır.');
                $this->redirect('/admin/users/admins/' . $id . '/edit');
            }
            $data['password'] = $password;
        }

        $roleIds = array_map('intval', (array) ($_POST['role_ids'] ?? []));

        try {
            if ($data !== []) {
                $this->users->update($id, $data);
            }
            $this->users->syncRoles($id, $roleIds);

            // Kendi hesabıysa → yetkileri anında yenile
            $current = $this->auth->user();
            if ($current !== null && (int) $current['id'] === $id) {
                $this->auth->refreshPermissions();
            }

            Flash::set('success', 'Kullanıcı rolleri güncellendi.');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/users/admins/' . $id . '/edit');
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id      = (int) $params['id'];
        $current = $this->auth->user();

        // Kendi hesabından admin yetkisini alamaz
        if ($current !== null && (int) $current['id'] === $id) {
            Flash::set('error', 'Kendi admin yetkilerinizi kaldıramazsınız.');
            $this->redirect('/admin/users/admins');
        }

        // Tüm rollerini kaldır (admin erişimini sil)
        $this->users->syncRoles($id, []);
        Flash::set('success', 'Kullanıcının admin yetkileri kaldırıldı.');
        $this->redirect('/admin/users/admins');
    }

    // ----------------------------------------------------------------
    //  Yardımcılar
    // ----------------------------------------------------------------

    /** Admin.* yetkisi içeren rol ID'lerini döndürür. */
    private function getAdminRoleIds(): array
    {
        try {
            $allRoles = QueryBuilder::table('um_roles')->get();
        } catch (Throwable $e) {
            return [];
        }

        $adminRoleIds = [];
        foreach ($allRoles as $role) {
            $perms = json_decode((string) ($role['permissions'] ?? '[]'), true) ?? [];
            foreach ($perms as $p) {
                if (strpos($p, 'admin.') === 0) {
                    $adminRoleIds[] = (int) $role['id'];
                    break;
                }
            }
        }

        return $adminRoleIds;
    }
}
