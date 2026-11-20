<?php $this->extend('layouts/admin'); ?>

<?php
$grid = $this->data('calendar_grid');
?>

<?php $this->section('title'); ?>Görevler & Google Tarzı Takvim Ajandası<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<style>
.g-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-top: 1px solid #cbd5e1;
    border-left: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
}
.g-cal-day-header {
    background: #f1f5f9;
    padding: 10px;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    color: #475569;
    border-right: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
}
.g-cal-cell {
    min-height: 110px;
    padding: 6px;
    border-right: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
    background: #ffffff;
    position: relative;
}
.g-cal-cell.other-month {
    background: #f8fafc;
    color: #94a3b8;
}
.g-cal-cell.is-today {
    background: #eff6ff;
}
.g-cal-date-num {
    font-weight: 700;
    font-size: 12px;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.g-cal-task-pill {
    display: block;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #ffffff;
    text-decoration: none;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.g-cal-task-pill:hover {
    opacity: 0.9;
}
</style>

<div class="page-toolbar">
    <div>
        <a class="btn btn-small <?= empty($this->data('current_status')) ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/tasks') ?>">Tüm Görevler</a>
        <a class="btn btn-small <?= $this->data('current_status') === 'pending' ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/tasks?status=pending') ?>">Bekleyenler</a>
        <a class="btn btn-small <?= $this->data('current_status') === 'completed' ? 'btn-primary' : 'btn-outline' ?>" href="<?= $this->route('/admin/crm/tasks?status=completed') ?>">Tamamlananlar</a>
        <a class="btn btn-small btn-ghost" href="<?= $this->route('/admin/crm/task-types') ?>">🏷️ Görev Türleri Yönetimi</a>
    </div>
    <a class="btn btn-primary" href="<?= $this->route('/admin/crm/tasks/create') ?>">+ Yeni Görev Ekle</a>
</div>

<!-- Google Calendar Style Monthly Grid -->
<section class="panel" style="margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2 class="panel-title" style="margin-bottom:0;">
            📅 Google Takvim Ajandası: <strong><?= $this->e($grid['month_name']) ?> <?= $grid['year'] ?></strong>
        </h2>
        <div>
            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/crm/tasks?year=' . $grid['prev_year'] . '&month=' . $grid['prev_month']) ?>">&larr; Önceki Ay</a>
            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/crm/tasks?year=' . date('Y') . '&month=' . date('n')) ?>">Bugün / Bu Ay</a>
            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/crm/tasks?year=' . $grid['next_year'] . '&month=' . $grid['next_month']) ?>">Sonraki Ay &rarr;</a>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="g-cal-grid">
        <div class="g-cal-day-header">Pazartesi</div>
        <div class="g-cal-day-header">Salı</div>
        <div class="g-cal-day-header">Çarşamba</div>
        <div class="g-cal-day-header">Perşembe</div>
        <div class="g-cal-day-header">Cuma</div>
        <div class="g-cal-day-header" style="color:#ef4444;">Cumartesi</div>
        <div class="g-cal-day-header" style="color:#ef4444;">Pazar</div>

        <?php foreach ($grid['weeks'] as $week): ?>
            <?php foreach ($week as $cell): ?>
                <div class="g-cal-cell <?= !$cell['is_current_month'] ? 'other-month' : '' ?> <?= $cell['is_today'] ? 'is-today' : '' ?>">
                    <div class="g-cal-date-num">
                        <span style="<?= $cell['is_today'] ? 'background:#2563eb; color:#fff; padding:1px 6px; border-radius:50%;' : '' ?>">
                            <?= $cell['day_number'] ?>
                        </span>
                        <a href="<?= $this->route('/admin/crm/tasks/create?due_date=' . $cell['date']) ?>" style="text-decoration:none; font-size:11px; color:#94a3b8;" title="Bu güne görev ekle">+</a>
                    </div>
                    <div>
                        <?php foreach ($cell['tasks'] as $t): ?>
                            <?php
                            $typeColor = $t['task_type']['color'] ?? '#2563eb';
                            $typeIcon = $t['task_type']['icon'] ?? '📋';
                            ?>
                            <a href="<?= $this->route('/admin/crm/tasks/' . (int) $t['id'] . '/edit') ?>" class="g-cal-task-pill" style="background:<?= $typeColor ?>;" title="<?= $this->e($t['title']) ?>">
                                <?= $typeIcon ?> <?= $this->e($t['title']) ?>
                                <?php if ($t['fee'] > 0): ?> (<?= number_format((float) $t['fee'], 0) ?> ₺)<?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- Task List Table -->
<section class="panel">
    <h3 class="panel-title" style="margin-bottom:12px;">📋 Görev Listesi Tablosu</h3>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Durum</th>
                    <th>Görev Türü</th>
                    <th>Görev Başlığı</th>
                    <th>İlgili Müşteri</th>
                    <th>Ücret (₺)</th>
                    <th>Son Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data('tasks', []) as $task): ?>
                    <tr>
                        <td>
                            <form method="post" action="<?= $this->route('/admin/crm/tasks/' . (int) $task['id'] . '/toggle') ?>" class="inline-form">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small <?= $task['status'] === 'completed' ? 'btn-success' : 'btn-outline' ?>" style="padding:2px 8px; font-size:11px;">
                                    <?= $task['status'] === 'completed' ? '✓ Tamamlandı' : '⭕ Bekliyor' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <?php if (!empty($task['task_type'])): ?>
                                <span class="badge" style="background:<?= $task['task_type']['color'] ?>; color:#fff; padding:2px 8px; border-radius:12px; font-size:11px;">
                                    <?= $task['task_type']['icon'] ?> <?= $this->e($task['task_type']['name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="<?= $task['status'] === 'completed' ? 'text-decoration:line-through; color:#94a3b8;' : '' ?>">
                                <?= $this->e($task['title']) ?>
                            </strong>
                        </td>
                        <td>
                            <?php if (!empty($task['contact_name'])): ?>
                                <a href="<?= $this->route('/admin/crm/contacts/' . (int) $task['contact_id']) ?>" style="color:#2563eb; font-weight:600;">
                                    <?= $this->e($task['contact_name']) ?>
                                </a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($task['fee'] > 0): ?>
                                <strong style="color:#10b981;"><?= number_format((float) $task['fee'], 2, ',', '.') ?> ₺</strong>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->e($task['due_date'] ?: '—') ?></td>
                        <td class="row-actions">
                            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/crm/tasks/' . (int) $task['id'] . '/edit') ?>">Düzenle</a>
                            <form method="post" action="<?= $this->route('/admin/crm/tasks/' . (int) $task['id'] . '/delete') ?>" class="inline-form js-confirm">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($this->data('tasks', []) === []): ?>
                    <tr><td colspan="7" class="muted">Görev bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
