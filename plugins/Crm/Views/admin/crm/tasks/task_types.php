<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Görev Türleri<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-small" href="<?php echo $this->route('/admin/crm/tasks'); ?>">← Görev Listesi</a>
    <a class="btn btn-small btn-primary" href="<?php echo $this->route('/admin/crm/task-types/create'); ?>">+ Yeni Görev Türü</a>
</div>

<?php if (empty($types)): ?>
    <p class="muted">Henüz görev türü eklenmemiş.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>İkon</th>
                <th>Tür Adı</th>
                <th>Renk</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $type): ?>
                <tr>
                    <td style="font-size:1.5rem;">
                        <?php echo $this->e($type['icon'] ?? '📋'); ?>
                    </td>
                    <td><?php echo $this->e($type['name']); ?></td>
                    <td>
                        <span style="display:inline-block;width:20px;height:20px;background:<?php echo $this->e($type['color'] ?? '#2563eb'); ?>;border-radius:4px;"></span>
                        <?php echo $this->e($type['color']); ?>
                    </td>
                    <td class="row-actions">
                        <a class="btn btn-small btn-outline" href="<?php echo $this->route('/admin/crm/task-types/' . $type['id'] . '/edit'); ?>">Düzenle</a>
                        <form method="post" action="<?php echo $this->route('/admin/crm/task-types/' . $type['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Bu görev türünü silmek istediğinizden emin misiniz?');">
                            <?php echo $csrf_field; ?>
                            <button type="submit" class="btn btn-small btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php $this->endSection(); ?>
