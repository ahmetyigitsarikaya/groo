<?php $this->extend('layouts/admin'); ?>

<?php
$templates = $this->data('templates', []);
$activeTemplate = $this->data('active_template', 'default');
?>

<?php $this->section('title'); ?>Görünüm & Şablonlar<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar" style="justify-content: space-between; align-items: center; display: flex;">
    <h2>Yüklü Şablonlar</h2>
    <div class="upload-form" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form action="<?php echo $this->route('/admin/themes/upload'); ?>" method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
            <?php echo $this->data('csrf_field'); ?>
            <label for="zip_file" style="font-weight: bold;">Yeni Şablon / Eklenti Yükle (.zip):</label>
            <input type="file" name="zip_file" id="zip_file" accept=".zip" required>
            <button type="submit" class="btn btn-primary btn-small">Yükle</button>
        </form>
    </div>
</div>

<div class="theme-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
    <?php if (empty($templates)): ?>
        <p class="muted">Sistemde yüklü şablon bulunamadı.</p>
    <?php else: ?>
        <?php foreach ($templates as $theme): ?>
            <?php $isActive = $theme['is_active']; ?>
            <div class="theme-card" style="border: 2px solid <?php echo $isActive ? '#10b981' : '#e5e7eb'; ?>; border-radius: 8px; padding: 15px; background: #fff; position: relative;">
                <?php if ($isActive): ?>
                    <span style="position: absolute; top: -10px; right: -10px; background: #10b981; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Aktif</span>
                <?php endif; ?>
                
                <h3 style="margin-top: 0; font-size: 1.25rem;"><?php echo $this->e($theme['name']); ?></h3>
                <p class="muted" style="font-size: 0.9rem;">Dizin: /templates/<?php echo $this->e($theme['slug']); ?></p>
                
                <div class="theme-actions" style="margin-top: 15px;">
                    <?php if (!$isActive): ?>
                        <form action="<?php echo $this->route('/admin/themes/' . $theme['slug'] . '/activate'); ?>" method="post" onsubmit="return confirm('Bu şablonu aktifleştirmek istediğinize emin misiniz?');">
                            <?php echo $this->data('csrf_field'); ?>
                            <button type="submit" class="btn btn-outline btn-small" style="width: 100%;">Aktifleştir</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-small" disabled style="width: 100%; opacity: 0.7; cursor: not-allowed; background: #e5e7eb; color: #374151;">Şu an kullanımda</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
