<?php $this->extend('layouts/admin'); ?>

<?php
$expense = $this->data('expense', []);
$contacts = $this->data('contacts', []);
$action = $this->route('/admin/crm/finance/create');
?>

<?php $this->section('title'); ?>Yeni Gider / Harcama Ekle<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/finance') ?>">&larr; Faturalara/Giderlere Dön</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form">
        <?= $this->data('csrf_field') ?>
        <input type="hidden" name="type" value="expense">
        <input type="hidden" name="status" value="paid">
        
        <div class="form-row">
            <label for="category">Gider Kategorisi <span class="muted">(örn: Personel Maaşı, Ofis Gideri)</span></label>
            <input type="text" id="category" name="category" value="<?= $this->e($expense['category'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <label for="description">Harcama Açıklaması / Detaylar <span class="muted">(isteğe bağlı)</span></label>
            <textarea id="description" name="items[0][description]" rows="3"><?= $this->e($expense['items'][0]['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <label for="contact_id">Ödeme Yapılan Tedarikçi / Çalışan <span class="muted">(isteğe bağlı)</span></label>
            <select id="contact_id" name="contact_id">
                <option value="">-- Genel Gider (Kişi/Firma Belirtmeden) --</option>
                <?php foreach ($contacts as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ((int) ($expense['contact_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= $this->e($c['name']) ?> (<?= strtoupper($this->e($c['type'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="due_date">İşlem / Ödeme Tarihi</label>
            <input type="date" id="due_date" name="due_date" value="<?= $this->e($expense['due_date'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-row">
            <label for="total_amount">Toplam Tutar (₺)</label>
            <input type="number" step="0.01" id="total_amount" name="total_amount" value="<?= $this->e($expense['total_amount'] ?? '') ?>" required style="font-size:1.2rem; font-weight:bold; color:#ef4444;">
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-danger" style="padding:12px 28px; font-size:1.05rem;">Gideri Kaydet</button>
        </div>
    </form>
</section>
<?php $this->endSection(); ?>
