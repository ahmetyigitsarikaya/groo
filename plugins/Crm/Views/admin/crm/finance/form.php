<?php $this->extend('layouts/admin'); ?>

<?php
$invoice = $this->data('invoice');
$contacts = $this->data('contacts', []);
$isEdit = (bool) $this->data('is_edit', false);
$action = $this->route('/admin/crm/finance/create');

$defaultItems = [
    ['description' => '', 'hours' => 1, 'unit_price' => 0, 'total' => 0]
];

$items = !empty($invoice['items']) ? (is_array($invoice['items']) ? $invoice['items'] : json_decode($invoice['items'], true)) : $defaultItems;
if (!is_array($items)) $items = $defaultItems;
?>

<?php $this->section('title'); ?>Yeni Fatura & Ek Süre/Saat Tanımlama<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/crm/finance') ?>">&larr; Faturalara Dön</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form" id="invoice-form">
        <?= $this->data('csrf_field') ?>

        <div class="form-row">
            <label for="invoice_number">Fatura Numarası</label>
            <input type="text" id="invoice_number" name="invoice_number" value="<?= $this->e($invoice['invoice_number'] ?? ('INV-' . date('Ymd') . '-' . rand(100, 999))) ?>" required>
        </div>

        <div class="form-row">
            <label for="contact_id">Fatura Kesilecek Müşteri / Cari <span class="muted">(isteğe bağlı)</span></label>
            <select id="contact_id" name="contact_id">
                <option value="">-- Listeden Seçin --</option>
                <?php foreach ($contacts as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ((int) ($invoice['contact_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= $this->e($c['name']) ?> (<?= strtoupper($this->e($c['type'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="manual_contact">Veya Manuel Müşteri/Firma Adı <span class="muted">(yukarıdan seçmezseniz)</span></label>
            <input type="text" id="manual_contact" name="manual_contact" placeholder="Örn: Ahmet Yılmaz">
        </div>

        <div class="form-row">
            <label for="due_date">Son Ödeme Tarihi</label>
            <input type="date" id="due_date" name="due_date" value="<?= $this->e($invoice['due_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>">
        </div>

        <div class="form-row">
            <label for="status">Ödeme Durumu</label>
            <select id="status" name="status">
                <option value="unpaid" <?= ($invoice['status'] ?? 'unpaid') === 'unpaid' ? 'selected' : '' ?>>Ödeme Bekliyor (Unpaid)</option>
                <option value="paid" <?= ($invoice['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Ödendi (Paid)</option>
                <option value="overdue" <?= ($invoice['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Gecikmede (Overdue)</option>
            </select>
        </div>

        <!-- Dynamic Billable Hours / Service Items Builder -->
        <div class="form-row">
            <label style="font-size:15px; font-weight:700;">💼 Hizmet & Ek Süre/Çalışma Saati Kalemleri (Billable Hours)</label>
            <p class="muted" style="margin-top:0; font-size:13px;">Projeye harcanan ekstra saat, gün veya hizmet kalemlerini girin. Toplam tutar otomatik hesaplanacaktır.</p>

            <table class="admin-table" style="margin-bottom:12px;" id="items-table">
                <thead>
                    <tr>
                        <th style="width:45%;">Hizmet / Ek Süre Açıklaması</th>
                        <th style="width:15%;">Süre (Saat/Gün)</th>
                        <th style="width:20%;">Birim Fiyat (₺)</th>
                        <th style="width:15%;">Toplam (₺)</th>
                        <th style="width:5%;"></th>
                    </tr>
                </thead>
                <tbody id="items-tbody"></tbody>
            </table>

            <button type="button" class="btn btn-small btn-outline" id="add-item-btn">+ Kalem / Ek Süre Ekle</button>
        </div>

        <div class="form-row" style="background:#f8fafc; padding:16px; border-radius:8px; border:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:1.1rem; font-weight:700; color:#0f172a;">FATURA GENEL TOPLAMI:</span>
            <span style="font-size:1.5rem; font-weight:800; color:#2563eb;" id="grand-total-display">0.00 ₺</span>
            <input type="hidden" id="total_amount" name="total_amount" value="<?= $this->e($invoice['total_amount'] ?? '0') ?>">
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-primary" style="padding:12px 28px; font-size:1.05rem;">Faturayı Kaydet ve Oluştur</button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var itemsTbody = document.getElementById('items-tbody');
    var addItemBtn = document.getElementById('add-item-btn');
    var grandTotalDisplay = document.getElementById('grand-total-display');
    var totalAmountInput = document.getElementById('total_amount');

    var items = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
    if (!Array.isArray(items) || items.length === 0) {
        items = [{ description: '', hours: 1, unit_price: 0, total: 0 }];
    }

    function renderItems() {
        itemsTbody.innerHTML = '';
        var grandTotal = 0;

        items.forEach(function(item, index) {
            var hours = parseFloat(item.hours) || 0;
            var unitPrice = parseFloat(item.unit_price) || 0;
            var total = hours * unitPrice;
            item.total = total;
            grandTotal += total;

            var tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="items[${index}][description]" value="${escapeHtml(item.description || '')}" placeholder="Örn: Ek Süre / Özel Geliştirme" style="width:100%;" required>
                </td>
                <td>
                    <input type="number" step="any" name="items[${index}][hours]" value="${hours}" class="item-hours" style="width:100%;" required>
                </td>
                <td>
                    <input type="number" step="any" name="items[${index}][unit_price]" value="${unitPrice}" class="item-price" style="width:100%;" required>
                </td>
                <td>
                    <strong class="item-total-str">${total.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</strong>
                </td>
                <td>
                    <button type="button" class="btn btn-small btn-danger" style="padding:2px 6px;" onclick="removeItem(${index})">X</button>
                </td>
            `;

            var hoursInput = tr.querySelector('.item-hours');
            var priceInput = tr.querySelector('.item-price');

            hoursInput.oninput = function() {
                items[index].hours = parseFloat(hoursInput.value) || 0;
                renderItems();
            };
            priceInput.oninput = function() {
                items[index].unit_price = parseFloat(priceInput.value) || 0;
                renderItems();
            };

            itemsTbody.appendChild(tr);
        });

        grandTotalDisplay.innerText = grandTotal.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
        totalAmountInput.value = grandTotal.toFixed(2);
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    window.removeItem = function(index) {
        items.splice(index, 1);
        if (items.length === 0) items.push({ description: '', hours: 1, unit_price: 0, total: 0 });
        renderItems();
    };

    addItemBtn.onclick = function() {
        items.push({ description: '', hours: 1, unit_price: 0, total: 0 });
        renderItems();
    };

    renderItems();
});
</script>
<?php $this->endSection(); ?>
