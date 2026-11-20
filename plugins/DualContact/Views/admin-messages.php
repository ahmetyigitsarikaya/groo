<?php
/**
 * @var array $messages
 * @var string $csrf_field
 */
?>
<?php $this->extend('layouts/admin'); ?>
<?php $this->section('title'); ?>Gelen Mesajlar<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="admin-header">
    <div class="header-content">
        <h1>Gelen Mesajlar</h1>
        <p>Sitenizdeki iletişim formundan gönderilen tüm mesajları buradan takip edebilirsiniz.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Mesaj Kutusu</div>
    <div class="card-body p-0">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="50">Durum</th>
                        <th>Gönderen</th>
                        <th>Konu</th>
                        <th>Tarih</th>
                        <th width="120">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 muted">Henüz hiç mesajınız yok.</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($messages as $msg): ?>
                    <tr class="<?= $msg['is_read'] ? 'read-message' : 'unread-message font-bold' ?>">
                        <td class="text-center">
                            <?php if ($msg['is_read']): ?>
                                <span class="badge" style="background:#e2e8f0;color:#475569;">Okundu</span>
                            <?php else: ?>
                                <span class="badge badge-primary">Yeni</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= $this->e($msg['name']) ?></div>
                            <small class="muted"><?= $this->e($msg['email']) ?> - <?= $this->e($msg['phone'] ?? '') ?></small>
                        </td>
                        <td><?= $this->e($msg['subject'] ?? 'Konu Yok') ?></td>
                        <td><small class="muted"><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></small></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline" onclick="viewMessage(<?= htmlspecialchars(json_encode($msg)) ?>)">Oku</button>
                                <form method="POST" action="<?= $this->route('/admin/contact-messages/delete') ?>" onsubmit="return confirm('Bu mesajı silmek istediğinize emin misiniz?');" style="display:inline;">
                                    <?= $csrf_field ?>
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger-color); border-color:var(--danger-color);">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Viewing Message -->
<div id="messageModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div class="card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y:auto; background:#ffffff;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 style="margin:0;" id="modalSubject">Mesaj Detayı</h3>
            <button onclick="closeModal()" class="btn btn-sm btn-outline" style="border:none; font-size:1.5rem; padding:0 10px;">&times;</button>
        </div>
        <div class="card-body">
            <div style="margin-bottom: 15px;">
                <strong>Gönderen:</strong> <span id="modalName"></span>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>E-Posta:</strong> <a href="" id="modalEmailLink"><span id="modalEmail"></span></a>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Telefon:</strong> <a href="" id="modalPhoneLink"><span id="modalPhone"></span></a>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Tarih:</strong> <span id="modalDate"></span>
            </div>
            <hr>
            <div style="margin-top: 15px; background:var(--bg-color); padding: 15px; border-radius:8px; white-space: pre-wrap;" id="modalMessageContent"></div>
            
            <form id="markReadForm" method="POST" action="<?= $this->route('/admin/contact-messages/read') ?>" class="mt-4">
                <?= $csrf_field ?>
                <input type="hidden" name="id" id="modalId">
                <button type="submit" id="btnMarkRead" class="btn btn-primary w-100" style="display:none;">Okundu Olarak İşaretle</button>
            </form>
        </div>
    </div>
</div>

<script>
function viewMessage(msg) {
    document.getElementById('modalSubject').textContent = msg.subject || 'Mesaj';
    document.getElementById('modalName').textContent = msg.name;
    document.getElementById('modalEmail').textContent = msg.email;
    document.getElementById('modalEmailLink').href = 'mailto:' + msg.email;
    document.getElementById('modalPhone').textContent = msg.phone || '-';
    if(msg.phone) {
        document.getElementById('modalPhoneLink').href = 'tel:' + msg.phone;
    } else {
        document.getElementById('modalPhoneLink').removeAttribute('href');
    }
    
    // Format date roughly (for demo, relying on raw format)
    document.getElementById('modalDate').textContent = msg.created_at;
    
    document.getElementById('modalMessageContent').textContent = msg.message;
    document.getElementById('modalId').value = msg.id;
    
    if (msg.is_read == 0) {
        document.getElementById('btnMarkRead').style.display = 'block';
    } else {
        document.getElementById('btnMarkRead').style.display = 'none';
    }
    
    document.getElementById('messageModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('messageModal').style.display = 'none';
}
</script>
<?php $this->endSection(); ?>
