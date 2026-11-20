<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?><?= $this->e($this->data('entity')['name'] ?? 'Entity') ?> Kayıtları<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<?php $entitySlug = rawurlencode((string) ($this->data('entity')['slug'] ?? '')); ?>

<div class="page-toolbar">
    <p class="muted">
        <code><?= $this->e($this->data('entity')['slug'] ?? '') ?></code> entity'si için
        <?= count($this->data('records', [])) ?> kayıt listeleniyor.
    </p>
    <a class="btn btn-primary" href="<?= $this->route('/admin/entities/' . $entitySlug . '/new') ?>">+ Yeni Kayıt</a>
</div>

<section class="panel">
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <?php foreach ($this->data('attributes', []) as $attribute): ?>
                        <th><?= $this->e($attribute['code']) ?><?php if ($attribute['required']): ?> *<?php endif; ?></th>
                    <?php endforeach; ?>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data('records', []) as $record): ?>
                    <?php $values = $record['data'] ?? []; ?>
                    <tr>
                        <td><?= (int) $record['id'] ?></td>
                        <?php foreach ($this->data('attributes', []) as $attribute): ?>
                            <?php $value = $values[$attribute['code']] ?? null; ?>
                            <td>
                                <?php if (is_array($value)): ?>
                                    <code class="cell-json"><?= $this->e(mb_strimwidth((string) json_encode($value, JSON_UNESCAPED_UNICODE), 0, 60, '…')) ?></code>
                                <?php elseif (is_bool($value)): ?>
                                    <?= $value ? 'evet' : 'hayır' ?>
                                <?php else: ?>
                                    <?= $this->e($value ?? '—') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="row-actions">
                            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/entities/' . $entitySlug . '/' . (int) $record['id']) ?>">Düzenle</a>
                            <form method="post" action="<?= $this->route('/admin/entities/' . $entitySlug . '/' . (int) $record['id'] . '/delete') ?>" class="inline-form js-confirm">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($this->data('records', []) === []): ?>
                    <tr><td colspan="<?= count($this->data('attributes', [])) + 2 ?>" class="muted">Henüz kayıt yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
