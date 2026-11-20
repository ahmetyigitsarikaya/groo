<?php $this->extend('layouts/admin'); ?>

<?php
$entity = $this->data('entity');
$entitySlug = rawurlencode((string) ($entity['slug'] ?? ''));
$record = $this->data('record');
$action = $record === null
    ? $this->route('/admin/entities/' . $entitySlug)
    : $this->route('/admin/entities/' . $entitySlug . '/' . (int) $record['id']);
?>

<?php $this->section('title'); ?><?= $record === null ? 'Yeni Kayıt' : 'Kaydı Düzenle' ?> — <?= $this->e($entity['name'] ?? '') ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/entities/' . $entitySlug) ?>">&larr; Listeye dön</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form">
        <?= $this->data('csrf_field') ?>

        <?php foreach ($this->data('attributes', []) as $attribute): ?>
            <?php
            $code = (string) $attribute['code'];
            $type = (string) $attribute['type'];
            $value = $this->data('values')[$code] ?? null;
            $fieldId = 'field_' . $code;
            ?>
            <div class="form-row">
                <label for="<?= $fieldId ?>">
                    <?= $this->e($code) ?>
                    <span class="muted">(<?= $this->e($type) ?><?= $attribute['required'] ? ', zorunlu' : '' ?>)</span>
                </label>

                <?php if ($type === 'boolean' || $type === 'bool'): ?>
                    <label class="switch">
                        <input type="checkbox" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="1" <?= $value ? 'checked' : '' ?>>
                        <span>Evet</span>
                    </label>
                <?php elseif ($type === 'text'): ?>
                    <textarea id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" rows="3"><?= $this->e($value) ?></textarea>
                <?php elseif (in_array($type, ['json', 'array', 'object'], true)): ?>
                    <textarea id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" rows="4" class="mono"><?= $this->e(is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $value) ?></textarea>
                <?php elseif (in_array($type, ['integer', 'int'], true)): ?>
                    <input type="number" step="1" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="<?= $this->e($value) ?>">
                <?php elseif (in_array($type, ['float', 'number', 'decimal'], true)): ?>
                    <input type="number" step="any" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="<?= $this->e($value) ?>">
                <?php elseif ($type === 'date'): ?>
                    <input type="date" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="<?= $this->e($value) ?>">
                <?php elseif ($type === 'datetime'): ?>
                    <input type="datetime-local" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="<?= $this->e($value) ?>">
                <?php else: ?>
                    <input type="text" id="<?= $fieldId ?>" name="fields[<?= $this->e($code) ?>]" value="<?= $this->e($value) ?>" <?= $attribute['required'] ? 'required' : '' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $record === null ? 'Kaydı Oluştur' : 'Değişiklikleri Kaydet' ?></button>
        </div>
    </form>
</section>
<?php $this->endSection(); ?>
