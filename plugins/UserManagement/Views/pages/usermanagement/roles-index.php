<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Roller<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.roles-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; margin-bottom:24px; }
.role-card  { background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.role-card-header { padding:16px 20px; display:flex; align-items:center; gap:12px; }
.role-color-swatch { width:40px; height:40px; border-radius:10px; flex-shrink:0; }
.role-card-name  { font-size:15px; font-weight:700; color:#111827; }
.role-card-count { font-size:11px; color:#9ca3af; }
.role-card-perms { padding:12px 20px; border-top:1px solid #f3f4f6; display:flex; flex-wrap:wrap; gap:4px; min-height:44px; }
.perm-chip { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px;
             font-weight:600; background:#f3f4f6; color:#374151; }
.role-card-footer { padding:10px 20px; border-top:1px solid #f3f4f6; display:flex; gap:6px; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="margin:0; font-size:16px; font-weight:700;">Discord Tarzı Roller</h2>
    <a href="<?= $this->route('/admin/users/roles/new') ?>" class="btn btn-primary">+ Yeni Rol</a>
</div>

<?php $roles = $this->data('roles', []); ?>
<?php if ($roles === []): ?>
    <p class="muted">Henüz rol tanımlanmamış.</p>
<?php else: ?>
<div class="roles-grid">
<?php foreach ($roles as $role): ?>
    <?php $perms = json_decode((string)($role['permissions'] ?? '[]'), true) ?? []; ?>
    <div class="role-card">
        <div class="role-card-header">
            <div class="role-color-swatch" style="background:<?= $this->e($role['color']) ?>"></div>
            <div>
                <div class="role-card-name"><?= $this->e($role['name']) ?></div>
                <div class="role-card-count"><?= count($perms) ?> yetki düğümü</div>
            </div>
        </div>
        <div class="role-card-perms">
            <?php if ($perms === []): ?>
                <span class="muted" style="font-size:11px;">Yetki tanımlanmamış</span>
            <?php else: ?>
                <?php foreach ($perms as $p): ?>
                    <span class="perm-chip"><?= $this->e($p) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="role-card-footer">
            <a href="<?= $this->route('/admin/users/roles/' . (int)$role['id'] . '/edit') ?>" class="btn btn-small btn-outline">Düzenle</a>
            <form method="post" action="<?= $this->route('/admin/users/roles/' . (int)$role['id'] . '/delete') ?>"
                  onsubmit="return confirm('Bu rolü silmek istiyor musunuz?')">
                <?= $this->data('csrf_field') ?>
                <button class="btn btn-small btn-danger">Sil</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php $this->endSection(); ?>
