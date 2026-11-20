<?php

declare(strict_types=1);

namespace Plugins\UserManagement\Controllers;

use Plugins\Admin\Support\Flash;
use Plugins\UserManagement\Repositories\RoleRepository;
use Throwable;

/**
 * RoleController — Discord tarzı rol yönetimi.
 * Liste, oluşturma, düzenleme, silme.
 */
final class RoleController extends BaseController
{
    private RoleRepository $roles;

    public function __construct()
    {
        parent::__construct();
        $this->roles = new RoleRepository();
    }

    public function index(array $params): void
    {
        $this->requireAuth();

        $this->render('pages/usermanagement/roles-index', [
            'roles'       => $this->roles->all(),
            'permissions' => RoleRepository::availablePermissions(),
        ]);
    }

    public function create(array $params): void
    {
        $this->requireAuth();

        $this->render('pages/usermanagement/role-form', [
            'role'        => null,
            'permissions' => RoleRepository::availablePermissions(),
            'role_perms'  => [],
        ]);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $name  = $this->input('name');
        $color = $this->input('color', '#99aab5');
        $perms = array_keys(array_filter((array) ($_POST['permissions'] ?? [])));

        try {
            if ($name === '') {
                throw new \RuntimeException('Rol adı boş olamaz.');
            }
            $this->roles->create($name, $color, $perms);
            Flash::set('success', '"' . $name . '" rolü oluşturuldu.');
            $this->redirect('/admin/users/roles');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/users/roles/new');
        }
    }

    public function edit(array $params): void
    {
        $this->requireAuth();

        $role = $this->roles->find((int) $params['id']);
        if ($role === null) {
            Flash::set('error', 'Rol bulunamadı.');
            $this->redirect('/admin/users/roles');
        }

        $this->render('pages/usermanagement/role-form', [
            'role'        => $role,
            'permissions' => RoleRepository::availablePermissions(),
            'role_perms'  => json_decode((string) ($role['permissions'] ?? '[]'), true) ?? [],
        ]);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id    = (int) $params['id'];
        $name  = $this->input('name');
        $color = $this->input('color', '#99aab5');
        $perms = array_keys(array_filter((array) ($_POST['permissions'] ?? [])));

        try {
            if ($name === '') {
                throw new \RuntimeException('Rol adı boş olamaz.');
            }
            $this->roles->update($id, $name, $color, $perms);
            Flash::set('success', 'Rol güncellendi.');
        } catch (Throwable $e) {
            Flash::set('error', $e->getMessage());
        }

        $this->redirect('/admin/users/roles/' . $id . '/edit');
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int) $params['id'];
        if ($this->roles->delete($id)) {
            Flash::set('success', 'Rol silindi.');
        } else {
            Flash::set('error', 'Rol silinemedi.');
        }
        $this->redirect('/admin/users/roles');
    }
}
