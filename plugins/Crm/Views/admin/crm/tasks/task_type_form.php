<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Görev Türü <?php echo isset($type) && $type ? 'Düzenle' : 'Ekle'; ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-small" href="<?php echo $this->route('/admin/crm/task-types'); ?>">← Görev Türleri Listesi</a>
</div>

<?php
$default = ['name' => '', 'color' => '#2563eb', 'icon' => '📋'];
$values = $type ?? $default;
$action = $is_edit ? $this->route('/admin/crm/task-types/' . $values['id'] . '/edit') : $this->route('/admin/crm/task-types/create');
?>
<form method="post" action="<?php echo $action; ?>">
    <?php echo $csrf_field; ?>
    <div class="form-group">
        <label for="name">Tür Adı</label>
        <input type="text" name="name" id="name" class="form-control" required value="<?php echo $this->e($values['name']); ?>">
    </div>
    <div class="form-group">
        <label for="color">Renk (hex)</label>
        <input type="color" name="color" id="color" class="form-control" value="<?php echo $this->e($values['color']); ?>">
    </div>
    <div class="form-group">
        <label for="icon">İkon (emoji)</label>
        <input type="text" name="icon" id="icon" class="form-control" value="<?php echo $this->e($values['icon']); ?>">
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Güncelle' : 'Kaydet'; ?></button>
</form>
<?php $this->endSection(); ?>
