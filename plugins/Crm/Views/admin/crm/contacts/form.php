<?php $this->extend('layouts/admin'); ?>

<?php
$contact = $this->data('contact');
$isEdit = (bool) $this->data('is_edit', false);
$action = $isEdit && isset($contact['id'])
    ? $this->route('/admin/crm/contacts/' . (int) $contact['id'] . '/edit')
    : $this->route('/admin/crm/contacts/create');

$tagsStr = is_array($contact['tags'] ?? null) ? implode(', ', $contact['tags']) : ($contact['tags'] ?? '');
$customJson = is_array($contact['custom_fields'] ?? null) ? json_encode($contact['custom_fields'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ($contact['custom_fields'] ?? '');
?>

<?php $this->section('title'); ?><?= $isEdit ? 'Müşteri Bilgilerini Düzenle' : 'Yeni Müşteri / Cari Ekle' ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/contacts') ?>">&larr; Müşteri Listesine Dön</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form">
        <?= $this->data('csrf_field') ?>

        <div class="form-row">
            <label for="name">Unvan / Müşteri Adı <span class="muted">(zorunlu)</span></label>
            <input type="text" id="name" name="name" value="<?= $this->e($contact['name'] ?? '') ?>" required placeholder="Acme Holding A.Ş. veya Ahmet Yılmaz">
        </div>

        <div class="form-row">
            <label for="type">Müşteri Tipi</label>
            <select id="type" name="type">
                <option value="b2b" <?= ($contact['type'] ?? 'b2b') === 'b2b' ? 'selected' : '' ?>>B2B (Kurumsal Şirket)</option>
                <option value="b2c" <?= ($contact['type'] ?? '') === 'b2c' ? 'selected' : '' ?>>B2C (Bireysel Müşteri)</option>
            </select>
        </div>

        <div class="form-row">
            <label for="status">CRM Durumu</label>
            <select id="status" name="status">
                <option value="lead" <?= ($contact['status'] ?? 'lead') === 'lead' ? 'selected' : '' ?>>Lead / Potansiyel Müşteri</option>
                <option value="customer" <?= ($contact['status'] ?? '') === 'customer' ? 'selected' : '' ?>>Müşteri (Aktif Cari)</option>
                <option value="passive" <?= ($contact['status'] ?? '') === 'passive' ? 'selected' : '' ?>>Pasif</option>
            </select>
        </div>

        <div class="form-row">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" value="<?= $this->e($contact['email'] ?? '') ?>" placeholder="ornek@sirket.com">
        </div>

        <div class="form-row">
            <label for="phone">Telefon Numarası</label>
            <input type="text" id="phone" name="phone" value="<?= $this->e($contact['phone'] ?? '') ?>" placeholder="+90 532 000 0000">
        </div>

        <div class="form-row">
            <label for="address">Adres / Şehir</label>
            <textarea id="address" name="address" rows="3" placeholder="Açık adres ve şehir bilgisi..."><?= $this->e($contact['address'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <label for="tags">Etiketler <span class="muted">(Virgülle ayırın)</span></label>
            <input type="text" id="tags" name="tags" value="<?= $this->e($tagsStr) ?>" placeholder="VIP, Kurumsal, Yazılım, Mobil">
        </div>

        <div class="form-row">
            <label for="custom_fields">Özel JSON Alanları (Vergi no, sektör vb.)</label>
            <textarea id="custom_fields" name="custom_fields" rows="4" class="mono" placeholder='{"vergi_no": "1234567890", "sektor": "Teknoloji"}'><?= $this->e($customJson) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Müşteriyi Güncelle' : 'Müşteriyi Kaydet' ?></button>
        </div>
    </form>
</section>
<?php $this->endSection(); ?>
