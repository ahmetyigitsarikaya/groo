<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Müşteriler & Cariler (CRM)<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <p class="muted">Toplam <?= count($this->data('contacts', [])) ?> müşteri kaydı listeleniyor.</p>
    <a class="btn btn-primary" href="<?= $this->route('/admin/crm/contacts/create') ?>">+ Yeni Müşteri Ekle</a>
</div>

<section class="panel">
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Unvan / Adı</th>
                    <th>Tür</th>
                    <th>Durum</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Etiketler</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->data('contacts', []) as $contact): ?>
                    <tr>
                        <td><?= (int) $contact['id'] ?></td>
                        <td>
                            <a href="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id']) ?>" style="font-weight:700; color:#2563eb; text-decoration:none;">
                                <?= $this->e($contact['name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:12px; font-size:12px;">
                                <?= strtoupper($this->e($contact['type'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $statusStyle = match ($contact['status']) {
                                'customer' => 'background:#10b981; color:#fff;',
                                'passive' => 'background:#6b7280; color:#fff;',
                                default => 'background:#f59e0b; color:#fff;',
                            };
                            $statusLabel = match ($contact['status']) {
                                'customer' => 'Müşteri',
                                'passive' => 'Pasif',
                                default => 'Lead / Potansiyel',
                            };
                            ?>
                            <span class="badge" style="<?= $statusStyle ?> padding:2px 8px; border-radius:12px; font-size:12px;">
                                <?= $statusLabel ?>
                            </span>
                        </td>
                        <td><?= $this->e($contact['email'] ?: '—') ?></td>
                        <td><?= $this->e($contact['phone'] ?: '—') ?></td>
                        <td>
                            <?php foreach ((array) ($contact['tags'] ?? []) as $tag): ?>
                                <span style="background:#dbeafe; color:#1e40af; padding:1px 6px; border-radius:4px; font-size:11px; margin-right:2px;"><?= $this->e($tag) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td class="row-actions">
                            <a class="btn btn-small btn-primary" href="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id']) ?>">Zaman Tüneli</a>
                            <a class="btn btn-small btn-outline" href="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id'] . '/edit') ?>">Düzenle</a>
                            <form method="post" action="<?= $this->route('/admin/crm/contacts/' . (int) $contact['id'] . '/delete') ?>" class="inline-form js-confirm">
                                <?= $this->data('csrf_field') ?>
                                <button type="submit" class="btn btn-small btn-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($this->data('contacts', []) === []): ?>
                    <tr><td colspan="8" class="muted">Henüz kayıtlı müşteri bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
