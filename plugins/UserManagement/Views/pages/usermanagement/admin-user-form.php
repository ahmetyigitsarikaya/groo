<?php
$admin      = $this->data('admin');        // null = yeni (kullanıcı seç)
$adminRoles = $this->data('admin_roles', []);
$allRoles   = $this->data('all_roles', []);
$allUsers   = $this->data('all_users', []);
$isNew      = $admin === null;
$adminId    = $isNew ? 0 : (int) $admin['id'];

$formAction = $isNew
    ? $this->route('/admin/users/admins/new')
    : $this->route('/admin/users/admins/' . $adminId . '/edit');

$adminRoleIds = array_column($adminRoles, 'id');
$current      = $this->data('admin_user', []);
$isSelf       = !$isNew && (int) ($current['id'] ?? 0) === $adminId;

$this->extend('layouts/admin');
?>

<?php $this->section('title'); ?>
    <?= $isNew ? 'Admin Yetkisi Ver' : 'Admin Rollerini Düzenle: ' . $this->e($admin['username'] ?? '') ?>
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.role-select-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:8px; margin-top:8px; }
.role-select-item { display:flex; align-items:center; gap:8px; padding:10px 12px;
                    border:2px solid #e5e7eb; border-radius:8px; cursor:pointer; transition:.15s; }
.role-select-item.checked { border-color:var(--role-color,#3b82f6); background:#f0f9ff; }
.role-dot  { width:12px; height:12px; border-radius:50%; flex-shrink:0; }

.perm-preview { margin-top:16px; padding:12px; background:#f8fafc; border:1px solid #e5e7eb;
                border-radius:8px; font-size:12px; min-height:44px; }
.perm-chip-sm { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px;
                font-weight:600; background:#e0f2fe; color:#0369a1; margin:2px; }
.perm-chip-admin { background:#fef3c7; color:#92400e; }

.user-search-wrap { position:relative; }
.user-select { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;
               font-size:13px; background:#fff; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <?php if ($isSelf): ?>
    <span style="font-size:12px; padding:4px 10px; background:#fef3c7; color:#92400e; border-radius:6px;">
        ⚠ Kendi hesabınızı düzenliyorsunuz.
    </span>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    <a href="<?= $this->route('/admin/users/admins') ?>" class="btn btn-ghost">← Geri</a>
</div>

<div class="panel" style="max-width:720px;">
    <h2 class="panel-title">
        <?= $isNew ? 'Kullanıcıya Admin Yetkisi Ver' : 'Admin Rollerini Düzenle' ?>
    </h2>

    <?php if (!$isNew): ?>
    <!-- Kullanıcı bilgisi -->
    <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:#f0f9ff;
                border:1px solid #bae6fd; border-radius:8px; margin-bottom:20px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#3b82f6; display:flex; align-items:center;
                    justify-content:center; color:#fff; font-weight:800; font-size:16px;">
            <?= strtoupper(substr((string)($admin['username'] ?? 'U'), 0, 1)) ?>
        </div>
        <div>
            <div style="font-weight:700; font-size:14px;"><?= $this->e($admin['username'] ?? '') ?></div>
            <div style="font-size:12px; color:#6b7280;"><?= $this->e($admin['email'] ?? '') ?></div>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $this->e($formAction) ?>" id="adminRoleForm">
        <?= $this->data('csrf_field') ?>

        <?php if ($isNew): ?>
        <!-- Kullanıcı Seçimi -->
        <div style="margin-bottom:20px;">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kullanıcı Seç</label>
            <select name="user_id" required class="user-select">
                <option value="">-- Kullanıcı seçin --</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= $this->e($u['username']) ?> &lt;<?= $this->e($u['email']) ?>&gt;</option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <!-- Şifre Değiştir (isteğe bağlı) -->
        <div style="margin-bottom:20px;">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Yeni Şifre <span style="font-weight:400; color:#9ca3af;">(boş bırakılırsa değişmez)</span>
            </label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800"
                   placeholder="En az 6 karakter">
        </div>
        <?php endif; ?>

        <!-- Rol Seçimi -->
        <div style="margin-bottom:20px;">
            <span class="block text-sm font-semibold text-gray-700 mb-1">
                Admin Rolleri Ata
                <span style="font-weight:400; color:#6b7280; font-size:11px;">
                    — admin.* yetkisi içeren roller admin paneline erişim sağlar
                </span>
            </span>
            <div class="role-select-grid" id="roleGrid">
            <?php foreach ($allRoles as $role): ?>
                <?php
                $checked = in_array((int)$role['id'], $adminRoleIds, true);
                $perms   = json_decode((string)($role['permissions'] ?? '[]'), true) ?? [];
                $hasAdminPerms = !empty(array_filter($perms, static fn(string $p): bool => strpos($p, 'admin.') === 0));
                ?>
                <div class="role-select-item <?= $checked ? 'checked' : '' ?>"
                     style="--role-color:<?= $this->e($role['color']) ?>"
                     data-role-id="<?= (int)$role['id'] ?>"
                     data-role-perms="<?= $this->e(json_encode($perms)) ?>"
                     onclick="toggleRole(this)">
                    <input type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>"
                           <?= $checked ? 'checked' : '' ?> style="display:none">
                    <span class="role-dot" style="background:<?= $this->e($role['color']) ?>"></span>
                    <div>
                        <div style="font-size:13px; font-weight:600;"><?= $this->e($role['name']) ?></div>
                        <div style="font-size:10px; color:#9ca3af;">
                            <?= count($perms) ?> yetki
                            <?php if ($hasAdminPerms): ?>
                            · <span style="color:#059669;">✓ Admin erişimi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <!-- Canlı yetki önizlemesi -->
            <div class="perm-preview" id="permPreview">
                <strong style="font-size:11px; color:#6b7280;">BİRLEŞİK YETKİLER:</strong>
                <div id="permList" style="margin-top:6px;"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= $isNew ? 'Admin Yetkisi Ver' : 'Rolleri Kaydet' ?>
        </button>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function() {
    window.toggleRole = function(el) {
        var cb = el.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        el.classList.toggle('checked', cb.checked);
        renderPermissions();
    };

    function renderPermissions() {
        var merged = {};
        document.querySelectorAll('#roleGrid .role-select-item.checked').forEach(function(el) {
            try {
                var perms = JSON.parse(el.dataset.rolePerms || '[]');
                perms.forEach(function(p) { merged[p] = true; });
            } catch(e) {}
        });

        var list = document.getElementById('permList');
        var keys = Object.keys(merged);

        if (keys.length === 0) {
            list.innerHTML = '<span style="color:#9ca3af; font-size:11px;">Hiç rol seçilmedi — bu kullanıcı admin paneline erişemez.</span>';
            return;
        }

        var adminPerms = keys.filter(function(p){ return p.indexOf('admin.') === 0; });
        var otherPerms = keys.filter(function(p){ return p.indexOf('admin.') !== 0; });

        var html = adminPerms.map(function(p){
            return '<span class="perm-chip-sm perm-chip-admin">' + p + '</span>';
        }).join('') + otherPerms.map(function(p){
            return '<span class="perm-chip-sm">' + p + '</span>';
        }).join('');

        list.innerHTML = html;
    }

    renderPermissions();
})();
</script>
<?php $this->endSection(); ?>
