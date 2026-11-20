<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Kontrol Paneli<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="stat-grid">
    <?php if (class_exists('Plugins\Crm\Support\CrmWidgetHelper')): ?>
    <div class="stat-card" style="border-left:4px solid #10b981;">
        <span class="stat-value" style="color:#10b981;"><?= number_format((float) $this->data('current_month_profit', 0), 2, ',', '.') ?> ₺</span>
        <span class="stat-label">Bu Ayki Net Kâr (Kazanç)</span>
    </div>
    <?php endif; ?>
    <div class="stat-card">
        <span class="stat-value"><?= (int) $this->data('active_plugin_count', 0) ?></span>
        <span class="stat-label">Aktif Eklenti</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= count($this->data('entities', [])) ?></span>
        <span class="stat-label">Dinamik Entity</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= (int) $this->data('total_records', 0) ?></span>
        <span class="stat-label">Toplam Kayıt</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $this->e($this->data('active_template')['name'] ?? '—') ?></span>
        <span class="stat-label">Aktif Şablon</span>
    </div>
</div>

<?php if (class_exists('Plugins\Crm\Support\CrmWidgetHelper')): ?>
    <?php $crmData = \Plugins\Crm\Support\CrmWidgetHelper::getDashboardWidgetsData(); ?>

    <!-- CRM Summary Widgets Grid -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:24px;">
        <!-- Widget 1: Yaklaşan Görevler & Takvim -->
        <div class="panel" style="margin-bottom:0;">
            <h2 class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span>📅 Yaklaşan Görevler & Takvim</span>
                <a href="<?= $this->route('/admin/crm/tasks') ?>" style="font-size:12px; text-decoration:none; color:#2563eb;">Tümü &rarr;</a>
            </h2>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($crmData['upcoming_tasks'] as $t): ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 12px; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:600; font-size:13px;"><?= $this->e($t['title']) ?></div>
                            <div style="font-size:11px; color:#64748b;">📅 Son Tarih: <?= $this->e($t['due_date'] ?: '—') ?></div>
                        </div>
                        <span class="badge" style="background:#dbeafe; color:#1e40af; font-size:11px; padding:2px 6px; border-radius:10px;"><?= strtoupper($t['priority']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($crmData['upcoming_tasks'] === []): ?>
                    <p class="muted" style="font-size:13px;">Bekleyen görev yok.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Widget 2: Geciken / Bekleyen Ödemeler -->
        <div class="panel" style="margin-bottom:0;">
            <h2 class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span>💰 Ön Muhasebe & Ödemeler</span>
                <a href="<?= $this->route('/admin/crm/finance') ?>" style="font-size:12px; text-decoration:none; color:#2563eb;">Tümü &rarr;</a>
            </h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                <div style="background:#fef3c7; border:1px solid #fcd34d; padding:10px; border-radius:6px;">
                    <div style="font-size:11px; font-weight:700; color:#92400e;">Bekleyen Ödemeler</div>
                    <div style="font-size:1.1rem; font-weight:800; color:#b45309;"><?= number_format((float) $crmData['finance_summary']['total_unpaid'], 2, ',', '.') ?> ₺</div>
                </div>
                <div style="background:#fee2e2; border:1px solid #fca5a5; padding:10px; border-radius:6px;">
                    <div style="font-size:11px; font-weight:700; color:#991b1b;">Geciken Ödemeler</div>
                    <div style="font-size:1.1rem; font-weight:800; color:#dc2626;"><?= number_format((float) $crmData['finance_summary']['total_overdue'], 2, ',', '.') ?> ₺</div>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($crmData['recent_invoices'] as $inv): ?>
                    <div style="font-size:12px; display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #e2e8f0;">
                        <span><strong><?= $this->e($inv['invoice_number']) ?></strong> (<?= $this->e($inv['contact_name']) ?>)</span>
                        <strong><?= number_format((float) $inv['total_amount'], 2, ',', '.') ?> ₺</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Widget 3: Son Müşteri Aktiviteleri & Bildirimler -->
        <div class="panel" style="margin-bottom:0;">
            <h2 class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span>🔔 Aktiviteler & Bildirimler</span>
                <a href="<?= $this->route('/admin/crm/contacts') ?>" style="font-size:12px; text-decoration:none; color:#2563eb;">Müşteriler &rarr;</a>
            </h2>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($crmData['unread_notifications'] as $notif): ?>
                    <div style="background:#eff6ff; border:1px solid #bfdbfe; padding:8px 10px; border-radius:6px; font-size:12px;">
                        <div style="font-weight:700; color:#1e40af;"><?= $this->e($notif['title']) ?></div>
                        <div style="color:#334155; font-size:11px;"><?= $this->e($notif['message']) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if ($crmData['unread_notifications'] === []): ?>
                    <p class="muted" style="font-size:13px;">Okunmamış yeni bildirim bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<section class="panel">
    <h2 class="panel-title">Dinamik Entity Kayıtları</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Entity</th>
                <th>Slug</th>
                <th>Kayıt Sayısı</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->data('entities', []) as $entity): ?>
                <tr>
                    <td><?= $this->e($entity['name']) ?></td>
                    <td><code><?= $this->e($entity['slug']) ?></code></td>
                    <td><?= (int) ($entity['record_count'] ?? 0) ?></td>
                    <td class="row-actions">
                        <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/entities/' . rawurlencode((string) $entity['slug'])) ?>">Yönet</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2 class="panel-title">Aktif Eklentiler</h2>
    <ul class="plugin-simple-list">
        <?php foreach ($this->data('active_plugins', []) as $slug => $plugin): ?>
            <?php $meta = $plugin->getMetadata(); ?>
            <li><strong><?= $this->e($meta['name'] ?? $slug) ?></strong> <span class="muted">v<?= $this->e($meta['version'] ?? '1.0.0') ?></span></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php $this->endSection(); ?>
