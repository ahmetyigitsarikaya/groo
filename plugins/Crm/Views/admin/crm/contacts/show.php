<?php $this->extend('layouts/admin'); ?>

<?php $contact = $this->data('contact'); ?>

<?php $this->section('title'); ?><?= $this->e($contact['name']) ?> — Müşteri Detayı & Zaman Tüneli<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<style>
.crm-profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}
.crm-timeline {
    position: relative;
    padding-left: 28px;
    margin-top: 16px;
    border-left: 2px solid #e2e8f0;
}
.crm-timeline-item {
    position: relative;
    margin-bottom: 24px;
}
.crm-timeline-item::before {
    content: '';
    position: absolute;
    left: -35px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #2563eb;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 2px #dbeafe;
}
.crm-timeline-item.badge-success::before { background: #10b981; box-shadow: 0 0 0 2px #d1fae5; }
.crm-timeline-item.badge-danger::before { background: #ef4444; box-shadow: 0 0 0 2px #fee2e2; }
.crm-timeline-item.badge-warning::before { background: #f59e0b; box-shadow: 0 0 0 2px #fef3c7; }

.crm-timeline-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.crm-timeline-title {
    font-weight: 700;
    font-size: 15px;
    color: #1e293b;
    margin-bottom: 4px;
}
.crm-timeline-date {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}
.crm-timeline-content {
    font-size: 14px;
    color: #334155;
    line-height: 1.5;
}
</style>

<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/contacts') ?>">&larr; Listeye Dön</a>
    <a class="btn btn-outline" href="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id'] . '/edit') ?>">Düzenle</a>
</div>

<div class="crm-profile-card">
    <div>
        <h2 style="font-size:1.6rem; margin-bottom:8px; color:#0f172a;"><?= $this->e($contact['name']) ?></h2>
        <p style="color:#64748b; margin-bottom:12px;">
            Tür: <strong><?= strtoupper($this->e($contact['type'])) ?></strong> | 
            Durum: <strong><?= ucfirst($this->e($contact['status'])) ?></strong>
        </p>
        <div style="font-size:14px; line-height:1.8;">
            <p>📧 <strong>E-posta:</strong> <?= $this->e($contact['email'] ?: '—') ?></p>
            <p>📞 <strong>Telefon:</strong> <?= $this->e($contact['phone'] ?: '—') ?></p>
            <p>📍 <strong>Adres:</strong> <?= $this->e($contact['address'] ?: '—') ?></p>
        </div>
    </div>
    <div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:14px; border-radius:8px;">
            <h4 style="font-size:13px; color:#64748b; text-transform:uppercase; margin-bottom:8px;">Etiketler</h4>
            <?php foreach ((array) ($contact['tags'] ?? []) as $tag): ?>
                <span style="background:#dbeafe; color:#1e40af; padding:3px 8px; border-radius:12px; font-size:12px; display:inline-block; margin-bottom:4px;"><?= $this->e($tag) ?></span>
            <?php endforeach; ?>
            <?php if (empty($contact['tags'])): ?>
                <p class="muted" style="font-size:12px;">Etiket yok</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Activity Note Form -->
<section class="panel" style="margin-bottom:24px;">
    <h3 class="panel-title" style="margin-bottom:12px;">+ Görüşme Notu / Aktivite Ekle</h3>
    <form method="post" action="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id'] . '/notes') ?>" class="admin-form">
        <?= $this->data('csrf_field') ?>
        <div style="display:grid; grid-template-columns: 200px 1fr auto; gap:12px; align-items:end;">
            <div>
                <label for="activity_type" style="font-size:12px;">Aktivite Türü</label>
                <select id="activity_type" name="activity_type">
                    <option value="note">📝 Görüşme Notu</option>
                    <option value="call">📞 Telefon Görüşmesi</option>
                    <option value="meeting">🤝 Toplantı</option>
                    <option value="email">✉️ E-posta</option>
                </select>
            </div>
            <div>
                <label for="note" style="font-size:12px;">Not / Açıklama</label>
                <input type="text" id="note" name="note" required placeholder="Görüşme detaylarını veya alınan notu yazın...">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </form>
</section>

<!-- Timeline View -->
<section class="panel">
    <h3 class="panel-title" style="margin-bottom:16px;">⏱️ Müşteri Zaman Tüneli (Timeline Feed)</h3>
    <div class="crm-timeline">
        <?php foreach ($this->data('timeline', []) as $item): ?>
            <div class="crm-timeline-item badge-<?= $this->e($item['badge'] ?? 'info') ?>">
                <div class="crm-timeline-card">
                    <div class="crm-timeline-title"><?= $item['title'] ?></div>
                    <div class="crm-timeline-date">📅 <?= $this->e($item['date']) ?></div>
                    <div class="crm-timeline-content"><?= $this->e($item['content']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($this->data('timeline', []) === []): ?>
            <p class="muted">Bu müşteriye ait henüz bir zaman tüneli aktivitesi kaydedilmedi.</p>
        <?php endif; ?>
    </div>
</section>
<?php $this->endSection(); ?>
