<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Traffic Analytics<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.stat-grid-traffic { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px; }
.t-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; display:flex; flex-direction:column; justify-content:center; }
.t-title { font-size:13px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
.t-value { font-size:28px; font-weight:800; color:#111827; margin-top:4px; }
.t-sub { font-size:12px; font-weight:500; margin-top:4px; display:flex; align-items:center; gap:4px; }
.t-sub.green { color:#059669; }
.t-sub.red { color:#dc2626; }
.t-sub.blue { color:#2563eb; }

.chart-container { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; margin-bottom:24px; }
.grid-2 { display:grid; grid-template-columns:3fr 2fr; gap:24px; }
@media(max-width:1024px){ .grid-2 { grid-template-columns:1fr; } }

.badge-score { padding:4px 8px; border-radius:6px; font-weight:700; font-size:12px; color:#fff; display:inline-block; text-align:center; min-width:40px; }
.badge-score.high { background:#059669; }
.badge-score.medium { background:#d97706; }
.badge-score.low { background:#dc2626; }

.live-feed { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
.live-item { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:6px; background:#f9fafb; border:1px solid #f3f4f6; }
.live-item.is-bot { background:#fee2e2; border-color:#fca5a5; }
.live-ip { font-family:monospace; font-weight:700; color:#1f2937; }
.live-url { font-size:12px; color:#4b5563; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.live-meta { font-size:10px; color:#6b7280; display:flex; flex-direction:column; text-align:right; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<?php $isExcluded = isset($_COOKIE['exclude_admin_traffic']) && $_COOKIE['exclude_admin_traffic'] === '1'; ?>
<div class="page-toolbar" style="margin-bottom: 20px; display:flex; justify-content:flex-end;">
    <div style="background:#fff; padding:10px 16px; border-radius:8px; border:1px solid #e5e7eb; display:flex; align-items:center; gap:10px;">
        <input type="checkbox" id="excludeAdminTraffic" <?= $isExcluded ? 'checked' : '' ?> style="cursor:pointer;">
        <label for="excludeAdminTraffic" style="font-size:14px; font-weight:600; color:#475569; margin:0; cursor:pointer;">
            Admin Sayfalarını (Benim Trafiğimi) Kaydetme
        </label>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('excludeAdminTraffic');
    if (cb) {
        cb.addEventListener('change', function() {
            var val = this.checked ? '1' : '0';
            document.cookie = "exclude_admin_traffic=" + val + "; path=/; max-age=" + (60*60*24*365);
            // alert('Tercihiniz kaydedildi!');
        });
    }
});
</script>
<div class="stat-grid-traffic">
    <div class="t-card">
        <span class="t-title">Toplam Trafik</span>
        <span class="t-value"><?= number_format($this->data('grand_total')) ?></span>
        <span class="t-sub blue">Tüm kaydedilmiş hitler</span>
    </div>
    <div class="t-card">
        <span class="t-title">İnsan Trafiği</span>
        <span class="t-value">%<?= $this->data('human_percent') ?></span>
        <span class="t-sub green">Gerçek kullanıcılar</span>
    </div>
    <div class="t-card">
        <span class="t-title">Bot Trafiği</span>
        <span class="t-value">%<?= $this->data('bot_percent') ?></span>
        <span class="t-sub red">Crawler ve botlar</span>
    </div>
    <div class="t-card">
        <span class="t-title">En Başarılı Sayfa</span>
        <?php $top = $this->data('top_page'); ?>
        <?php if($top): ?>
            <span class="t-value" style="font-size:18px; line-height:34px;" title="<?= $this->e($top['url']) ?>">
                <?= strlen($top['url']) > 20 ? substr($this->e($top['url']), 0, 17).'...' : $this->e($top['url']) ?>
            </span>
            <span class="t-sub green">Skor: <?= (int)$top['success_score'] ?> / 100</span>
        <?php else: ?>
            <span class="t-value">-</span>
        <?php endif; ?>
    </div>
</div>

<div class="chart-container">
    <h3 style="margin-top:0; font-size:15px; margin-bottom:16px;">Son 7 Günlük Trafik (İnsan vs Bot)</h3>
    <canvas id="trafficChart" height="70"></canvas>
</div>

<div class="grid-2">
    <!-- Sayfa Performans Tablosu -->
    <div class="panel" style="margin-bottom:0;">
        <h3 class="panel-title">Sayfa Performans (Success Score)</h3>
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>İnsan</th>
                        <th>Tekil</th>
                        <th>Bot</th>
                        <th>Skor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($this->data('pages', []) as $page): ?>
                        <?php 
                        $score = (int) $page['success_score'];
                        $badgeClass = $score >= 80 ? 'high' : ($score >= 50 ? 'medium' : 'low');
                        ?>
                        <tr>
                            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= $this->e($page['url']) ?>">
                                <?= $this->e($page['url']) ?>
                            </td>
                            <td><?= number_format((int)$page['total_views']) ?></td>
                            <td><?= number_format((int)$page['unique_views']) ?></td>
                            <td><?= number_format((int)$page['bot_views']) ?></td>
                            <td>
                                <span class="badge-score <?= $badgeClass ?>"><?= $score ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Canlı Akış -->
    <div class="panel" style="margin-bottom:0;">
        <h3 class="panel-title">Canlı Akış (Son 20 İstek)</h3>
        <ul class="live-feed">
            <?php foreach($this->data('live_feed', []) as $live): ?>
                <li class="live-item <?= $live['is_bot'] ? 'is-bot' : '' ?>">
                    <div>
                        <div class="live-ip"><?= $this->e($live['ip_address']) ?></div>
                        <div class="live-url" title="<?= $this->e($live['url']) ?>"><?= $this->e($live['url']) ?></div>
                    </div>
                    <div class="live-meta">
                        <strong><?= $live['is_bot'] ? '🤖 ' . $this->e($live['bot_name'] ?? 'Bot') : '👤 İnsan (' . ucfirst($this->e($live['device_type'])) . ')' ?></strong>
                        <span><?= date('H:i:s', strtotime($live['created_at'])) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trafficChart').getContext('2d');
    
    const labels = <?= json_encode($this->data('chart_labels')) ?>;
    const humans = <?= json_encode($this->data('chart_humans')) ?>;
    const bots   = <?= json_encode($this->data('chart_bots')) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'İnsan (Gerçek)',
                    data: humans,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Bot (Crawler)',
                    data: bots,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            interaction: { mode: 'nearest', axis: 'x', intersect: false },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
<?php $this->endSection(); ?>
