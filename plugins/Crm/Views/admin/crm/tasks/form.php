<?php $this->extend('layouts/admin'); ?>

<?php
$task = $this->data('task');
$contacts = $this->data('contacts', []);
$taskTypes = $this->data('task_types', []);
$isEdit = (bool) $this->data('is_edit', false);
$action = $isEdit && isset($task['id'])
    ? $this->route('/admin/crm/tasks/' . (int) $task['id'] . '/edit')
    : $this->route('/admin/crm/tasks/create');

$dueDate = $_GET['due_date'] ?? ($task['due_date'] ?? date('Y-m-d'));
?>

<?php $this->section('title'); ?><?= $isEdit ? 'Görevi Düzenle' : 'Yeni Görev / Plan Ekle' ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/tasks') ?>">&larr; Görev Listesine Dön</a>
    <a class="btn btn-small btn-ghost" href="<?= $this->route('/admin/crm/task-types') ?>">🏷️ Görev Türleri Yönetimi</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form">
        <?= $this->data('csrf_field') ?>

        <div class="form-row">
            <label for="title">Görev Başlığı <span class="muted">(zorunlu)</span></label>
            <input type="text" id="title" name="title" value="<?= $this->e($task['title'] ?? '') ?>" required placeholder="Görev veya plan adı...">
        </div>

        <div class="form-row">
            <label for="task_type_id">Görev Türü / Kategorisi</label>
            <select id="task_type_id" name="task_type_id">
                <option value="">-- Görev Türü Seçin --</option>
                <?php foreach ($taskTypes as $tt): ?>
                    <option value="<?= (int) $tt['id'] ?>" <?= ((int) ($task['task_type_id'] ?? 0)) === (int) $tt['id'] ? 'selected' : '' ?>>
                        <?= $tt['icon'] ?> <?= $this->e($tt['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="contact_id">İlgili Müşteri / Cari <span class="muted">(isteğe bağlı)</span></label>
            <select id="contact_id" name="contact_id">
                <option value="">-- Müşteri Seçilmedi --</option>
                <?php foreach ($contacts as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ((int) ($task['contact_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= $this->e($c['name']) ?> (<?= strtoupper($this->e($c['type'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="fee">Alınacak Ücret (₺) <span class="muted">(isteğe bağlı - ön muhasebeye otomatik aktarılabilir)</span></label>
            <input type="number" step="any" id="fee" name="fee" value="<?= $this->e($task['fee'] ?? '0') ?>" placeholder="0.00">
        </div>

        <?php if (!$isEdit): ?>
            <div class="form-row">
                <label class="switch">
                    <input type="checkbox" name="auto_invoice" value="1" checked>
                    <span><strong>Ön Muhasebeye Otomatik Fatura/Gelir Olarak Aktar</strong> (Alınacak ücret > 0 ise otomatik fatura oluşturur)</span>
                </label>
            </div>
        <?php endif; ?>

        <div class="form-row">
            <label for="due_date">Son Tarih (Due Date)</label>
            <input type="date" id="due_date" name="due_date" value="<?= $this->e($dueDate) ?>">
        </div>

        <div class="form-row">
            <label for="priority">Öncelik Seviyesi</label>
            <select id="priority" name="priority">
                <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>🟢 Düşük (Low)</option>
                <option value="medium" <?= ($task['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>🔵 Normal (Medium)</option>
                <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>🔴 Yüksek (High)</option>
            </select>
        </div>

        <div class="form-row">
            <label for="status">Görev Durumu</label>
            <select id="status" name="status">
                <option value="pending" <?= ($task['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Bekliyor (Pending)</option>
                <option value="completed" <?= ($task['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Tamamlandı (Completed)</option>
            </select>
        </div>

        <div class="form-row">
            <label for="description">Açıklama / Detaylar</label>
            <textarea id="description" name="description" rows="4" placeholder="Göreve dair yapılmak istenen iş adımları..."><?= $this->e($task['description'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Görevi Güncelle' : 'Görevi Kaydet' ?></button>
        </div>
    </form>
</section>
<?php $this->endSection(); ?>
