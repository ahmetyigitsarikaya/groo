<?php
$user      = $this->data('user');       // null = yeni kayıt
$userRoles = $this->data('user_roles', []);
$allRoles  = $this->data('all_roles', []);
$apiKeys   = $this->data('api_keys', []);
$tabs      = $this->data('profile_tabs', []);
$isNew     = $user === null;
$userId    = $isNew ? 0 : (int) $user['id'];

$formAction = $isNew
    ? $this->route('/admin/users/new')
    : $this->route('/admin/users/' . $userId . '/edit');

$userRoleIds = array_column($userRoles, 'id');
$this->extend('layouts/admin');
?>

<?php $this->section('title'); ?><?= $isNew ? 'Yeni Kullanıcı' : $this->e($user['username']) ?><?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.um-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 24px; }
@media(max-width:640px){ .um-form-grid{ grid-template-columns:1fr; } }

.um-tabs { display:flex; gap:0; border-bottom:2px solid #e5e7eb; margin-bottom:20px; }
.um-tab  { padding:8px 18px; font-size:13px; font-weight:600; color:#6b7280; cursor:pointer;
           border-bottom:2px solid transparent; margin-bottom:-2px; background:none; border-top:0;
           border-left:0; border-right:0; }
.um-tab.active { color:#1e3a5f; border-bottom-color:#1e3a5f; }
.um-tab-panel  { display:none; }
.um-tab-panel.active { display:block; }

.role-select-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:8px; }
.role-select-item { display:flex; align-items:center; gap:8px; padding:10px 12px;
                    border:2px solid #e5e7eb; border-radius:8px; cursor:pointer; transition:.15s; }
.role-select-item:has(input:checked) { border-color:var(--role-color,#3b82f6); background:#f0f9ff; }
.role-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }

.api-key-row { display:flex; justify-content:space-between; align-items:center;
               padding:8px 12px; background:#f8fafc; border:1px solid #e5e7eb;
               border-radius:6px; margin-bottom:6px; font-size:12px; }
.api-key-mask { font-family:monospace; color:#374151; }
.api-key-label { color:#6b7280; font-size:11px; }

.hook-tabs-area { border:1px dashed #d1d5db; border-radius:8px; padding:16px; background:#fafafa;
                  font-size:13px; color:#6b7280; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <a href="<?= $this->route('/admin/users') ?>" class="btn btn-ghost">← Listeye Dön</a>
</div>

<div class="panel">
    <?php if (!$isNew): ?>
    <!-- Tab Navigasyonu -->
    <div class="um-tabs" id="um-tabs">
        <button class="um-tab active" data-tab="general">👤 Genel</button>
        <button class="um-tab" data-tab="roles">🛡 Roller</button>
        <button class="um-tab" data-tab="apikeys">🔑 API Anahtarları</button>
        <?php foreach ($tabs as $tab): ?>
            <?php if (isset($tab['id'], $tab['label'])): ?>
            <button class="um-tab" data-tab="<?= $this->e($tab['id']) ?>">
                <?= isset($tab['icon']) ? $this->e($tab['icon']) . ' ' : '' ?><?= $this->e($tab['label']) ?>
            </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Genel Bilgiler -->
    <div class="um-tab-panel active" id="panel-general">
        <form method="post" action="<?= $this->e($formAction) ?>">
            <?= $this->data('csrf_field') ?>

            <div class="um-form-grid">
                <label class="block mb-4">
                    <span class="block text-sm font-semibold text-gray-700 mb-1">Kullanıcı Adı</span>
                    <input type="text" name="username" value="<?= $this->e($user['username'] ?? '') ?>" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800">
                </label>
                <label class="block mb-4">
                    <span class="block text-sm font-semibold text-gray-700 mb-1">E-posta</span>
                    <input type="email" name="email" value="<?= $this->e($user['email'] ?? '') ?>" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800">
                </label>
                <label class="block mb-4">
                    <span class="block text-sm font-semibold text-gray-700 mb-1">
                        Şifre <?= $isNew ? '' : '<span style="font-weight:400;color:#9ca3af;">(boş bırakılırsa değişmez)</span>' ?>
                    </span>
                    <input type="password" name="password" <?= $isNew ? 'required' : '' ?>
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800">
                </label>
                <label class="block mb-4">
                    <span class="block text-sm font-semibold text-gray-700 mb-1">Durum</span>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800">
                        <?php foreach (['active' => 'Aktif', 'banned' => 'Yasaklı', 'pending' => 'Beklemede'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($user['status'] ?? 'active') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <?php if ($isNew): ?>
            <!-- Yeni kullanıcıda roller de bu formda -->
            <div style="margin-bottom:16px;">
                <span class="block text-sm font-semibold text-gray-700 mb-2">Roller</span>
                <div class="role-select-grid">
                <?php foreach ($allRoles as $role): ?>
                    <label class="role-select-item" style="--role-color:<?= $this->e($role['color']) ?>">
                        <input type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>" style="display:none">
                        <span class="role-dot" style="background:<?= $this->e($role['color']) ?>"></span>
                        <span style="font-size:13px; font-weight:600;"><?= $this->e($role['name']) ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary"><?= $isNew ? 'Kullanıcı Oluştur' : 'Değişiklikleri Kaydet' ?></button>
        </form>
    </div>

    <?php if (!$isNew): ?>

    <!-- Roller Tab -->
    <div class="um-tab-panel" id="panel-roles">
        <form method="post" action="<?= $this->e($this->route('/admin/users/' . $userId . '/edit')) ?>">
            <?= $this->data('csrf_field') ?>
            <input type="hidden" name="username" value="<?= $this->e($user['username'] ?? '') ?>">
            <input type="hidden" name="email"    value="<?= $this->e($user['email'] ?? '') ?>">
            <input type="hidden" name="status"   value="<?= $this->e($user['status'] ?? 'active') ?>">

            <p style="font-size:13px; color:#6b7280; margin-bottom:16px;">
                Kullanıcıya atanacak rolleri seçin. Roller, yetki düğümlerini belirler.
            </p>
            <div class="role-select-grid" style="margin-bottom:20px;">
            <?php foreach ($allRoles as $role): ?>
                <?php $checked = in_array((int)$role['id'], $userRoleIds, true); ?>
                <label class="role-select-item" style="--role-color:<?= $this->e($role['color']) ?>">
                    <input type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>" <?= $checked ? 'checked' : '' ?> style="display:none">
                    <span class="role-dot" style="background:<?= $this->e($role['color']) ?>"></span>
                    <div>
                        <div style="font-size:13px; font-weight:600;"><?= $this->e($role['name']) ?></div>
                        <?php
                        $perms = json_decode((string)($role['permissions'] ?? '[]'), true) ?? [];
                        ?>
                        <div style="font-size:10px; color:#9ca3af;"><?= count($perms) ?> yetki</div>
                    </div>
                </label>
            <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Rolleri Güncelle</button>
        </form>
    </div>

    <!-- API Anahtarları Tab -->
    <div class="um-tab-panel" id="panel-apikeys">
        <div style="margin-bottom:16px;">
            <h3 style="font-size:14px; font-weight:700; margin-bottom:4px;">API Anahtarı Oluştur</h3>
            <p style="font-size:12px; color:#6b7280; margin-bottom:12px;">
                Anahtar sadece oluşturulduğunda bir kez gösterilir. Lütfen güvenli bir yerde saklayın.
            </p>
            <form method="post" action="<?= $this->route('/admin/users/' . $userId . '/api-keys/generate') ?>" style="display:flex; gap:8px; align-items:flex-end;">
                <?= $this->data('csrf_field') ?>
                <div style="flex:1;">
                    <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Etiket (isteğe bağlı)</label>
                    <input type="text" name="label" placeholder="Örn: Mobil Uygulama"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="btn btn-primary">🔑 Oluştur</button>
            </form>
        </div>

        <?php if ($apiKeys !== []): ?>
        <div>
            <h3 style="font-size:14px; font-weight:700; margin-bottom:8px;">Mevcut Anahtarlar</h3>
            <?php foreach ($apiKeys as $k): ?>
            <div class="api-key-row">
                <div>
                    <div class="api-key-mask">groo_••••••••••<?= substr((string)$k['api_key'], -6) ?></div>
                    <div class="api-key-label">
                        <?= $this->e($k['label'] ?: 'Etiketsiz') ?> &middot;
                        <?= $k['is_active'] ? '<span style="color:#065f46;">Aktif</span>' : '<span style="color:#991b1b;">İptal</span>' ?> &middot;
                        Oluşturulma: <?= $this->e(substr((string)$k['created_at'], 0, 10)) ?>
                        <?php if ($k['last_used_at']): ?>
                         &middot; Son kullanım: <?= $this->e(substr((string)$k['last_used_at'], 0, 10)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex; gap:6px;">
                    <?php if ($k['is_active']): ?>
                    <form method="post" action="<?= $this->route('/admin/users/' . $userId . '/api-keys/' . (int)$k['id'] . '/revoke') ?>">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-outline">İptal</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?= $this->route('/admin/users/' . $userId . '/api-keys/' . (int)$k['id'] . '/delete') ?>"
                          onsubmit="return confirm('Bu API anahtarını silmek istiyor musunuz?')">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-danger">Sil</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="muted">Bu kullanıcıya ait API anahtarı yok.</p>
        <?php endif; ?>
    </div>

    <!-- Hook ile eklenen dinamik sekmeler -->
    <?php foreach ($tabs as $tab): ?>
        <?php if (!isset($tab['id'])) continue; ?>
        <div class="um-tab-panel" id="panel-<?= $this->e($tab['id']) ?>">
            <?php if (isset($tab['content'])): ?>
                <?= $tab['content'] ?>
            <?php else: ?>
                <div class="hook-tabs-area">
                    Bu sekme <code><?= $this->e($tab['id']) ?></code> ID'li kanca ile eklendi.
                    İçeriği görüntülemek için sekmede <code>content</code> anahtarı sağlayın.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function() {
    var tabs   = document.querySelectorAll('#um-tabs .um-tab');
    var panels = document.querySelectorAll('.um-tab-panel');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            panels.forEach(function(p) { p.classList.remove('active'); });

            tab.classList.add('active');
            var panel = document.getElementById('panel-' + tab.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // Rol kartı tıklama (checkbox görünmez, label tıklanıyor)
    document.querySelectorAll('.role-select-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var cb = item.querySelector('input[type=checkbox]');
            if (cb) cb.checked = !cb.checked;
        });
    });
})();
</script>
<?php $this->endSection(); ?>
