<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Eklentiler<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<section class="panel">
    <h2 class="panel-title">Sistem Eklentileri</h2>

    <?php $flash = $this->data('flash', []); ?>
    <?php if (!empty($flash['success'])): ?>
        <div class="alert alert-success">✅ <?= $this->e($flash['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['info'])): ?>
        <div class="alert alert-info">ℹ️ <?= $this->e($flash['info']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])): ?>
        <div class="alert alert-error">❌ <?= $this->e($flash['error']) ?></div>
    <?php endif; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Eklenti</th>
                <th>Sürüm</th>
                <th>Açıklama</th>
                <th>Durum</th>
                <th style="text-align:center;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->data('discovered', []) as $item): ?>
                <tr>
                    <td><strong><?= $this->e($item['name']) ?></strong> <span class="muted text-sm">(<?= $this->e($item['dir']) ?>)</span></td>
                    <td><span class="badge badge-muted"><?= $this->e($item['version']) ?></span></td>
                    <td class="muted text-sm"><?= $this->e($item['description']) ?></td>
                    <td>
                        <?php if ($item['active']): ?>
                            <span class="badge badge-success">✓ Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <form method="POST" action="<?= $this->route('/admin/plugins/' . $item['slug'] . '/toggle') ?>" style="display:inline;">
                            <?= $this->data('csrf_field') ?>
                            <?php if ($item['active']): ?>
                                <button type="submit"
                                        onclick="return confirm('&quot;<?= $this->e($item['name']) ?>&quot; eklentisini pasifleştirmek istediğinize emin misiniz?')"
                                        class="btn btn-small btn-danger">
                                    Pasifleştir
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-small btn-success">
                                    ⚡ Aktifleştir
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted text-sm" style="margin-top:12px;">
        Bir eklenti aktifleştirilirken <code>Migrations/</code> klasörü varsa tablolar otomatik kurulur.
        Değişiklikler <code>.env</code> dosyasına kaydedilir.
    </p>
</section>

<section class="panel">
    <h2 class="panel-title">Aktif Şablon</h2>
    <?php $template = $this->data('active_template'); ?>
    <?php if (is_array($template)): ?>
        <p>
            <strong><?= $this->e($template['name'] ?? '—') ?></strong>
            <span class="badge badge-primary" style="margin-left:6px;">v<?= $this->e($template['version'] ?? '1.0.0') ?></span>
        </p>
        <p class="muted text-sm" style="margin-top:6px;"><?= $this->e($template['description'] ?? '') ?></p>
    <?php else: ?>
        <p class="muted">Aktif bir şablon tanımlı değil (<code>ACTIVE_TEMPLATE</code>).</p>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
