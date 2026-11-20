<?php $this->extend('layouts/admin'); ?>

<?php $inv = $this->data('invoice'); ?>

<?php $this->section('title'); ?>Fatura #<?= $this->e($inv['invoice_number']) ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/finance') ?>">&larr; Faturalara Dön</a>
    <div>
        <button type="button" class="btn btn-outline" onclick="window.print()">🖨️ Faturayı Yazdır</button>
        <?php if ($inv['status'] !== 'paid'): ?>
            <form method="post" action="<?= $this->route('/admin/crm/finance/' . (int) $inv['id'] . '/pay') ?>" class="inline-form">
                <?= $this->data('csrf_field') ?>
                <button type="submit" class="btn btn-success">✓ Ödendi Olarak İşaretle</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #invoice-print-area, #invoice-print-area * {
        visibility: visible;
    }
    #invoice-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        border: none !important;
    }
    .page-toolbar {
        display: none !important;
    }
}
</style>

<section class="panel" id="invoice-print-area" style="background:#ffffff; border:1px solid #e2e8f0; padding:32px; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:start; border-bottom:2px solid #e2e8f0; padding-bottom:20px; margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.8rem; color:#0f172a; margin-bottom:4px;">FATURA / ÖDEME BİLDİRİMİ</h1>
            <p style="color:#64748b; font-size:14px;">Fatura No: <strong><?= $this->e($inv['invoice_number']) ?></strong></p>
        </div>
        <div style="text-align:right;">
            <?php
            $statusStyle = match ($inv['status']) {
                'paid' => 'background:#dcfce7; color:#166534; border:1px solid #86efac;',
                'overdue' => 'background:#fee2e2; color:#991b1b; border:1px solid #fca5a5;',
                default => 'background:#fef3c7; color:#92400e; border:1px solid #fcd34d;',
            };
            $statusLabel = match ($inv['status']) {
                'paid' => 'ÖDENDİ',
                'overdue' => 'GECİKTİ',
                default => 'ÖDEME BEKLİYOR',
            };
            ?>
            <div style="<?= $statusStyle ?> padding:6px 16px; border-radius:20px; font-weight:800; font-size:14px; display:inline-block;">
                <?= $statusLabel ?>
            </div>
            <p style="color:#64748b; font-size:13px; margin-top:8px;">Düzenleme: <?= date('d.m.Y', strtotime($inv['created_at'])) ?></p>
            <p style="color:#64748b; font-size:13px;">Son Ödeme: <strong><?= date('d.m.Y', strtotime($inv['due_date'])) ?></strong></p>
        </div>
    </div>

    <!-- Contact Info -->
    <div style="margin-bottom:32px; background:#f8fafc; padding:16px; border-radius:8px; border:1px solid #e2e8f0;">
        <h3 style="font-size:12px; text-transform:uppercase; color:#64748b; margin-bottom:6px;">Sayın / Müşteri Bilgileri:</h3>
        <h4 style="font-size:1.1rem; color:#0f172a; margin-bottom:4px;"><?= $this->e($inv['contact_name']) ?></h4>
        <p style="color:#475569; font-size:14px;">📧 <?= $this->e($inv['contact_email'] ?: '—') ?></p>
        <p style="color:#475569; font-size:14px;">📍 <?= $this->e($inv['contact_address'] ?: '—') ?></p>
    </div>

    <!-- Items Table -->
    <table class="admin-table" style="margin-bottom:24px;">
        <thead>
            <tr>
                <th>Hizmet / Ek Süre Kalem Açıklaması</th>
                <th style="text-align:center;">Süre (Saat/Gün)</th>
                <th style="text-align:right;">Birim Fiyat</th>
                <th style="text-align:right;">Toplam Tutar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ((array) $inv['items'] as $item): ?>
                <tr>
                    <td><strong><?= $this->e($item['description'] ?? '') ?></strong></td>
                    <td style="text-align:center;"><?= number_format((float) ($item['hours'] ?? 1), 1) ?> Sa/Gün</td>
                    <td style="text-align:right;"><?= number_format((float) ($item['unit_price'] ?? 0), 2, ',', '.') ?> ₺</td>
                    <td style="text-align:right;"><strong><?= number_format((float) ($item['total'] ?? 0), 2, ',', '.') ?> ₺</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align:right; border-top:2px solid #0f172a; padding-top:16px;">
        <span style="font-size:1.2rem; font-weight:700; color:#475569;">GENEL TOPLAM:</span>
        <span style="font-size:1.8rem; font-weight:800; color:#2563eb; margin-left:12px;"><?= number_format((float) $inv['total_amount'], 2, ',', '.') ?> ₺</span>
    </div>
</section>
<?php $this->endSection(); ?>
