<?php $this->extend('layouts/admin'); ?>

<?php
$summary = $this->data('summary');
?>

<?php $this->section('title'); ?>Ön Muhasebe & Faturalandırma (CRM)<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<!-- Financial Summary Cards -->
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid #10b981;">
        <span class="stat-value"><?= number_format((float) ($summary['total_paid_income'] ?? 0), 2, ',', '.') ?> ₺</span>
        <span class="stat-label">Toplam Tahsilat (Kazanç)</span>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444;">
        <span class="stat-value"><?= number_format((float) ($summary['total_expense'] ?? 0), 2, ',', '.') ?> ₺</span>
        <span class="stat-label">Toplam Gider / Harcama</span>
    </div>
    <div class="stat-card" style="border-left:4px solid #2563eb;">
        <span class="stat-value"><?= number_format((float) ($summary['net_profit'] ?? 0), 2, ',', '.') ?> ₺</span>
        <span class="stat-label">Net Kasa (Kâr)</span>
    </div>
    <div class="stat-card" style="border-left:4px solid #f59e0b;">
        <span class="stat-value"><?= number_format((float) ($summary['total_unpaid'] ?? 0), 2, ',', '.') ?> ₺</span>
        <span class="stat-label">Bekleyen Tahsilatlar</span>
    </div>
</div>

<div class="panel" style="margin-bottom:24px; padding:16px;">
    <h3 style="margin-top:0; margin-bottom:16px; font-size:1.1rem; color:#1e293b;">6 Aylık Gelir & Gider Analizi</h3>
    <div id="finance-chart" style="min-height:300px;"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawData = <?= json_encode($this->data('monthly_stats', []), JSON_UNESCAPED_UNICODE) ?>;
    if(!rawData || rawData.length === 0) return;

    const labels = rawData.map(r => r.label);
    const incomeData = rawData.map(r => r.income);
    const expenseData = rawData.map(r => r.expense);

    const options = {
        series: [{
            name: 'Gelir (Kazanç)',
            data: incomeData
        }, {
            name: 'Gider (Harcama)',
            data: expenseData
        }],
        chart: {
            type: 'area',
            height: 300,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#10b981', '#ef4444'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: labels },
        yaxis: {
            labels: { formatter: (val) => val.toLocaleString('tr-TR') + ' ₺' }
        },
        tooltip: {
            y: { formatter: (val) => val.toLocaleString('tr-TR') + ' ₺' }
        }
    };

    new ApexCharts(document.querySelector("#finance-chart"), options).render();
});
</script>

<div class="page-toolbar">
    <div>
        <a class="btn btn-small <?= empty($this->data('current_status')) ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/finance') ?>">Tüm Faturalar</a>
        <a class="btn btn-small <?= $this->data('current_status') === 'unpaid' ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/finance?status=unpaid') ?>">Bekleyenler</a>
        <a class="btn btn-small <?= $this->data('current_status') === 'paid' ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/finance?status=paid') ?>">Ödenenler</a>
        <a class="btn btn-small <?= $this->data('current_status') === 'overdue' ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/finance?status=overdue') ?>">Gecikenler</a>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="btn btn-danger" href="<?= $this->route('/admin/crm/finance/expense/create') ?>">+ Yeni Gider / Harcama Ekle</a>
        <a class="btn btn-primary" href="<?= $this->route('/admin/crm/finance/create') ?>">+ Yeni Fatura & Ek Süre Ekle</a>
    </div>
</div>

<section class="panel">
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tür</th>
                    <th>Fatura No / ID</th>
                    <th>Müşteri / Cari</th>
                    <th>Toplam Tutar</th>
                    <th>Son Ödeme Tarihi</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data('invoices', []) as $inv): ?>
                    <tr>
                        <td>
                            <?php if ($inv['type'] === 'expense'): ?>
                                <span style="color:#ef4444; font-weight:bold;">📉 GİDER</span><br>
                                <small class="muted"><?= $this->e($inv['category']) ?></small>
                            <?php else: ?>
                                <span style="color:#10b981; font-weight:bold;">📈 GELİR (Fatura)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $this->route('/admin/crm/finance/' . (int) $inv['id']) ?>" style="font-weight:700; color:#2563eb; text-decoration:none;">
                                <?= $this->e($inv['invoice_number']) ?>
                            </a>
                        </td>
                        <td><strong><?= $this->e($inv['contact_name']) ?></strong></td>
                        <td>
                            <strong style="font-size:1.05rem; color: <?= $inv['type'] === 'expense' ? '#ef4444' : '#10b981' ?>;">
                                <?= $inv['type'] === 'expense' ? '-' : '+' ?><?= number_format((float) $inv['total_amount'], 2, ',', '.') ?> ₺
                            </strong>
                        </td>
                        <td><?= $this->e($inv['due_date'] ?: '—') ?></td>
                        <td>
                            <?php
                            $statusStyle = match ($inv['status']) {
                                'paid' => 'background:#10b981; color:#fff;',
                                'overdue' => 'background:#ef4444; color:#fff;',
                                default => 'background:#f59e0b; color:#fff;',
                            };
                            $statusLabel = match ($inv['status']) {
                                'paid' => 'Ödendi',
                                'overdue' => 'Gecikti',
                                default => 'Ödeme Bekliyor',
                            };
                            ?>
                            <span class="badge" style="<?= $statusStyle ?> padding:2px 8px; border-radius:12px; font-size:12px;">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td class="row-actions">
                            <a class="btn btn-small btn-primary" href="<?= $this->route('/admin/crm/finance/' . (int) $inv['id']) ?>">İncele</a>
                            <?php if ($inv['status'] !== 'paid'): ?>
                                <form method="post" action="<?= $this->route('/admin/crm/finance/' . (int) $inv['id'] . '/pay') ?>" class="inline-form">
                                    <?= $this->data('csrf_field') ?>
                                    <button type="submit" class="btn btn-small btn-success">✓ Ödendi</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= $this->route('/admin/crm/finance/' . (int) $inv['id'] . '/delete') ?>" class="inline-form js-confirm">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($this->data('invoices', []) === []): ?>
                    <tr><td colspan="6" class="muted">Fatura kaydı bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
