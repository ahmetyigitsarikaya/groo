<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Yazılar (Content)<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <p class="muted">Toplam <?= count($this->data('posts', [])) ?> yazı listeleniyor.</p>
    <a class="btn btn-primary" href="<?= $this->route('/admin/posts/create') ?>">+ Yeni Yazı</a>
</div>

<section class="panel">
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Başlık</th>
                    <th>Slug</th>
                    <th>Durum</th>
                    <th>Yayın Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data('posts', []) as $post): ?>
                    <tr>
                        <td><?= (int) $post['id'] ?></td>
                        <td><strong><?= $this->e($post['title']) ?></strong></td>
                        <td><code><?= $this->e($post['slug']) ?></code></td>
                        <td>
                            <?php if (($post['status'] ?? '') === 'published'): ?>
                                <span class="badge badge-success" style="background:#10b981;color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;">Yayınlandı</span>
                            <?php else: ?>
                                <span class="badge badge-secondary" style="background:#6b7280;color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;">Taslak</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->e($post['published_at'] ?? '—') ?></td>
                        <td class="row-actions">
                            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/posts/' . (int) $post['id'] . '/edit') ?>">Düzenle</a>
                            <form method="post" action="<?= $this->route('/admin/posts/' . (int) $post['id'] . '/delete') ?>" class="inline-form js-confirm">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($this->data('posts', []) === []): ?>
                    <tr><td colspan="6" class="muted">Henüz yazı eklenmemiş.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
