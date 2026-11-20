<?php
$role      = $this->data('role');       // null = yeni
$allPerms  = $this->data('permissions', []);
$rolePerms = $this->data('role_perms', []);
$isNew     = $role === null;
$roleId    = $isNew ? 0 : (int)$role['id'];
$formAction = $isNew
    ? $this->route('/admin/users/roles/new')
    : $this->route('/admin/users/roles/' . $roleId . '/edit');
$this->extend('layouts/admin');
?>

<?php $this->section('title'); ?><?= $isNew ? 'Yeni Rol' : 'Rol Düzenle: ' . $this->e($role['name']) ?><?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.perm-group { margin-bottom:20px; }
.perm-group-title { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
                    color:#9ca3af; margin-bottom:8px; }
.perm-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:6px; }
.perm-item { display:flex; align-items:center; gap:8px; padding:8px 10px;
             border:1px solid #e5e7eb; border-radius:6px; cursor:pointer; transition:.12s; }
.perm-item:has(input:checked) { border-color:#3b82f6; background:#eff6ff; }
.perm-item input { width:14px; height:14px; accent-color:#2563eb; flex-shrink:0; }
.perm-item label { font-size:12px; font-weight:500; color:#374151; cursor:pointer; flex:1; }
.perm-item code  { font-size:10px; color:#9ca3af; display:block; }

.color-preview { width:36px; height:36px; border-radius:8px; border:2px solid #e5e7eb;
                 flex-shrink:0; transition:.2s; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <a href="<?= $this->route('/admin/users/roles') ?>" class="btn btn-ghost">← Rollere Dön</a>
</div>

<div class="panel" style="max-width:700px;">
    <h2 class="panel-title"><?= $isNew ? 'Yeni Rol Oluştur' : 'Rolü Düzenle' ?></h2>

    <form method="post" action="<?= $this->e($formAction) ?>">
        <?= $this->data('csrf_field') ?>

        <div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
            <label style="flex:1;">
                <span class="block text-sm font-semibold text-gray-700 mb-1">Rol Adı</span>
                <input type="text" name="name" value="<?= $this->e($role['name'] ?? '') ?>" required
                       placeholder="Örn: Moderatör"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-800">
            </label>
            <label style="width:160px;">
                <span class="block text-sm font-semibold text-gray-700 mb-1">Renk (Discord HEX)</span>
                <div style="display:flex; gap:6px; align-items:center;">
                    <div class="color-preview" id="colorPreview"
                         style="background:<?= $this->e($role['color'] ?? '#99aab5') ?>"></div>
                    <input type="text" name="color" id="colorInput"
                           value="<?= $this->e($role['color'] ?? '#99aab5') ?>"
                           maxlength="7" pattern="#[0-9a-fA-F]{6}"
                           placeholder="#99aab5"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-800">
                </div>
            </label>
        </div>

        <!-- Hızlı renk paleti -->
        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:20px;">
            <?php foreach(['#e74c3c','#e67e22','#f1c40f','#2ecc71','#1abc9c','#3498db','#9b59b6','#e91e63','#ff5722','#607d8b','#99aab5','#ffffff'] as $clr): ?>
            <button type="button" onclick="setColor('<?= $clr ?>')"
                    style="width:24px;height:24px;border-radius:50%;background:<?= $clr ?>;
                           border:2px solid #e5e7eb;cursor:pointer;padding:0;"></button>
            <?php endforeach; ?>
        </div>

        <!-- Yetkiler -->
        <div style="margin-bottom:24px;">
            <span class="block text-sm font-semibold text-gray-700 mb-3">Yetki Düğümleri</span>
            <?php foreach ($allPerms as $group => $groupPerms): ?>
            <div class="perm-group">
                <div class="perm-group-title"><?= $this->e(ucfirst($group)) ?></div>
                <div class="perm-grid">
                <?php foreach ($groupPerms as $permKey => $permLabel): ?>
                    <?php $checked = in_array($permKey, $rolePerms, true); ?>
                    <div class="perm-item">
                        <input type="checkbox" name="permissions[<?= $this->e($permKey) ?>]"
                               id="perm_<?= str_replace('.', '_', $permKey) ?>"
                               value="1" <?= $checked ? 'checked' : '' ?>>
                        <label for="perm_<?= str_replace('.', '_', $permKey) ?>">
                            <?= $this->e($permLabel) ?>
                            <code><?= $this->e($permKey) ?></code>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isNew ? 'Rol Oluştur' : 'Kaydet' ?></button>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function setColor(hex) {
    document.getElementById('colorInput').value   = hex;
    document.getElementById('colorPreview').style.background = hex;
}
document.getElementById('colorInput').addEventListener('input', function() {
    var v = this.value;
    if (/^#[0-9a-fA-F]{6}$/.test(v)) {
        document.getElementById('colorPreview').style.background = v;
    }
});
</script>
<?php $this->endSection(); ?>
