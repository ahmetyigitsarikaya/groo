<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Admin Hesapları<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.legacy-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px;
                font-weight:700; text-transform:uppercase; background:#e5e7eb; color:#374151; }
.role-chip-sm  { display:inline-block; padding:1px 7px; border-radius:12px; font-size:10px;
                 font-weight:600; color:#fff; margin:1px; }
.self-row td  { background:#fffbeb !important; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="margin:0; font-size:16px; font-weight:700;">Admin Panel Hesapları</h2>
    <a href="<?= $this->route('/admin/users/admins/new') ?>" class="btn btn-primary">+ Yeni Admin</a>
</div>

<section class="panel">
    <p style="font-size:12px; color:#6b7280; margin-bottom:16px;">
        Bu listede admin paneline giriş yapabilen hesaplar yer alır.
        Her hesaba <strong>um_roles</strong> atanarak yetki seti belirlenir.
    </p>

    <?php $admins  = $this->data('admins', []);
          $current = $this->data('admin_user', []); ?>

    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Ad</th>
                <th>E-posta</th>
                <th>Atanmış Roller (Yetkiler)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
            <?php $isSelf = (int)($current['id'] ?? 0) === (int)$a['id']; ?>
            <tr <?= $isSelf ? 'class="self-row"' : '' ?>>
                <td><?= (int)$a['id'] ?></td>
                <td>
                    <strong><?= $this->e($a['username'] ?? $a['name']) ?></strong>
                    <?php if ($isSelf): ?>
                        <span style="font-size:10px; color:#92400e; background:#fef3c7; padding:1px 6px; border-radius:8px; margin-left:4px;">Sen</span>
                    <?php endif; ?>
                </td>
                <td><?= $this->e($a['email']) ?></td>
                <td>
                    <?php $roles = $a['um_roles'] ?? []; ?>
                    <?php if ($roles === []): ?>
                        <span class="muted" style="font-size:11px;">Rol atanmamış</span>
                    <?php else: ?>
                        <?php foreach ($roles as $r): ?>
                            <span class="role-chip-sm" style="background:<?= $this->e($r['color']) ?>"><?= $this->e($r['name']) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td class="row-actions" style="white-space:nowrap;">
                    <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/users/admins/' . (int)$a['id'] . '/edit') ?>">Düzenle</a>

                    <?php if (!$isSelf): ?>
                    <form style="display:inline" method="post" action="<?= $this->route('/admin/users/admins/' . (int)$a['id'] . '/delete') ?>"
                          onsubmit="return confirm('Bu admin hesabını silmek istiyor musunuz?')">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-danger">Sil</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php $this->endSection(); ?>
