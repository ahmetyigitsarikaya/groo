<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Kullanıcılar<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.um-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
.um-stat  { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px 20px; }
.um-stat-val  { font-size:2rem; font-weight:800; color:#1e3a5f; line-height:1; }
.um-stat-lbl  { font-size:12px; color:#6b7280; margin-top:4px; }

.status-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
.status-active  { background:#d1fae5; color:#065f46; }
.status-banned  { background:#fee2e2; color:#991b1b; }
.status-pending { background:#fef3c7; color:#92400e; }

.role-chip { display:inline-block; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; margin:1px; color:#fff; }
.action-form { display:inline; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="um-stats">
    <div class="um-stat">
        <div class="um-stat-val"><?= (int) $this->data('total', 0) ?></div>
        <div class="um-stat-lbl">Toplam Kullanıcı</div>
    </div>
    <div class="um-stat">
        <div class="um-stat-val" style="color:#065f46"><?= (int) ($this->data('stats', [])['active'] ?? 0) ?></div>
        <div class="um-stat-lbl">Aktif</div>
    </div>
    <div class="um-stat">
        <div class="um-stat-val" style="color:#991b1b"><?= (int) ($this->data('stats', [])['banned'] ?? 0) ?></div>
        <div class="um-stat-lbl">Yasaklı</div>
    </div>
</div>

<section class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2 class="panel-title" style="margin:0;">Kullanıcı Listesi</h2>
        <a href="<?= $this->route('/admin/users/new') ?>" class="btn btn-primary">+ Yeni Kullanıcı</a>
    </div>

    <?php $users = $this->data('users', []); ?>
    <?php if ($users === []): ?>
        <p class="muted">Henüz kullanıcı yok.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Durum</th>
                <th>Roller</th>
                <th>Kayıt Tarihi</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><strong><?= $this->e($u['username']) ?></strong></td>
                <td><?= $this->e($u['email']) ?></td>
                <td>
                    <span class="status-badge status-<?= $this->e($u['status']) ?>">
                        <?= $this->e($u['status']) ?>
                    </span>
                </td>
                <td>
                    <?php
                    // Roller zaten UserRepository::all() ile gelmiyor; burada lazy load yerine
                    // basit bir JOIN sorgusu ile gelen veriyi kullan (controller'da extend edilebilir)
                    ?>
                    <span class="muted" style="font-size:11px;">—</span>
                </td>
                <td style="font-size:12px; color:#6b7280;"><?= $this->e(substr((string)$u['created_at'], 0, 10)) ?></td>
                <td class="row-actions" style="white-space:nowrap;">
                    <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/users/' . (int)$u['id'] . '/edit') ?>">Düzenle</a>

                    <?php if ($u['status'] !== 'banned'): ?>
                    <form class="action-form" method="post" action="<?= $this->route('/admin/users/' . (int)$u['id'] . '/ban') ?>"
                          onsubmit="return confirm('Kullanıcıyı yasaklamak istiyor musunuz?')">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-danger">Yasakla</button>
                    </form>
                    <?php else: ?>
                    <form class="action-form" method="post" action="<?= $this->route('/admin/users/' . (int)$u['id'] . '/unban') ?>">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-outline">Yasağı Kaldır</button>
                    </form>
                    <?php endif; ?>

                    <form class="action-form" method="post" action="<?= $this->route('/admin/users/' . (int)$u['id'] . '/delete') ?>"
                          onsubmit="return confirm('Bu kullanıcıyı kalıcı olarak silmek istiyor musunuz?')">
                        <?= $this->data('csrf_field') ?>
                        <button class="btn btn-small btn-danger">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php // Basit sayfalama ?>
    <?php if ((int)$this->data('total_pages', 1) > 1): ?>
    <div style="display:flex; justify-content:center; gap:8px; margin-top:16px;">
        <?php for ($p = 1; $p <= (int)$this->data('total_pages'); $p++): ?>
            <a href="?page=<?= $p ?>" class="btn btn-small <?= $p === (int)$this->data('page') ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
