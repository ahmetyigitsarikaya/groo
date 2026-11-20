<?php
/**
 * @var array $pages
 * @var string $csrf_field
 */
?>
<?php $this->extend('layouts/admin'); ?>
<?php $this->section('title'); ?>Hata Sayfaları<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="admin-header">
    <div class="header-content">
        <h1>Hata Sayfaları Yönetimi</h1>
        <p>Sitenizde oluşan 404, 500 gibi hatalarda ziyaretçilere gösterilecek özel sayfaları tasarlayın.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Yeni Hata Sayfası (veya Düzenle)</div>
            <div class="card-body">
                <form method="POST" action="<?= $this->route('/admin/error-pages/save') ?>" class="admin-form">
                    <?= $csrf_field ?>
                    <input type="hidden" name="id" id="form_id" value="0">
                    
                    <div class="form-row">
                        <label>Hata Kodu (HTTP Status) <span class="text-danger">*</span></label>
                        <input type="number" name="error_code" id="form_error_code" placeholder="Örn: 404, 500, 403" required>
                    </div>

                    <div class="form-row">
                        <label>Sayfa Başlığı <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="form_title" placeholder="Örn: Sayfa Bulunamadı" required>
                    </div>

                    <div class="form-row">
                        <label>Açıklama (Mesaj)</label>
                        <textarea name="message" id="form_message" rows="4" placeholder="Ziyaretçiye gösterilecek açıklayıcı hata metni..."></textarea>
                    </div>

                    <div class="form-row">
                        <label>Özel Resim/İllüstrasyon URL (Opsiyonel)</label>
                        <input type="url" name="image_url" id="form_image_url" placeholder="https://...">
                        <small class="muted mt-1 d-block">Özel bir illüstrasyon göstermek isterseniz tam URL giriniz.</small>
                    </div>

                    <div class="form-row mt-3">
                        <label class="switch">
                            <input type="checkbox" name="is_active" id="form_is_active" value="1" checked> Aktif Et (Gösterilsin)
                        </label>
                    </div>

                    <div class="form-actions mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Kaydet</button>
                        <button type="button" class="btn btn-outline w-100" onclick="resetForm()" id="btn_cancel" style="display:none;">İptal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Kayıtlı Hata Sayfaları</div>
            <div class="card-body p-0">
                <div class="table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="80">Kod</th>
                                <th>Başlık</th>
                                <th>Durum</th>
                                <th width="120">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 muted">Henüz özel bir hata sayfası eklenmemiş.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><span class="badge badge-muted"><?= $this->e($page['error_code']) ?></span></td>
                                <td><strong><?= $this->e($page['title']) ?></strong></td>
                                <td>
                                    <?php if ($page['is_active']): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#fee2e2;color:#991b1b;">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick="editPage(<?= htmlspecialchars(json_encode($page)) ?>)">
                                        Düzenle
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editPage(page) {
    document.getElementById('form_id').value = page.id;
    document.getElementById('form_error_code').value = page.error_code;
    document.getElementById('form_title').value = page.title;
    document.getElementById('form_message').value = page.message || '';
    document.getElementById('form_image_url').value = page.image_url || '';
    document.getElementById('form_is_active').checked = page.is_active == 1;
    
    document.getElementById('btn_cancel').style.display = 'block';
    
    // Scroll to top on mobile
    if (window.innerWidth < 992) {
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
}

function resetForm() {
    document.getElementById('form_id').value = '0';
    document.getElementById('form_error_code').value = '';
    document.getElementById('form_title').value = '';
    document.getElementById('form_message').value = '';
    document.getElementById('form_image_url').value = '';
    document.getElementById('form_is_active').checked = true;
    
    document.getElementById('btn_cancel').style.display = 'none';
}
</script>
<?php $this->endSection(); ?>
