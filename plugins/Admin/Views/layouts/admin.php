<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($this->sectionContent('title', 'Admin')) ?> | <?= $this->e($_ENV['APP_NAME'] ?? 'Groo Admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="<?= $this->route('/admin/assets/css/admin.css') ?>">
    <?php $this->yieldSection('styles'); ?>
    <script>window.GROO_ADMIN_URL = '<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('')) ?>';</script>
</head>
<body class="admin-body">
<div class="admin-shell">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <?php $this->component('admin-sidebar'); ?>

    <div class="admin-main">
        <header class="admin-header">
            <div class="admin-header-left">
                <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
                <h1 class="admin-header-title"><?= $this->yieldSection('title') ?: 'Kontrol Paneli' ?></h1>
            </div>
            <div class="admin-header-user">
                <?php if (class_exists('Plugins\Crm\Support\CrmWidgetHelper')): ?>
                    <?= \Plugins\Crm\Support\CrmWidgetHelper::renderNotificationBell() ?>
                <?php endif; ?>
            </div>
        </header>

        <main class="admin-content">
            <?php foreach ($this->data('flash', []) as $type => $message): ?>
                <div class="alert alert-<?= $this->e($type) ?>"><?= $this->e($message) ?></div>
            <?php endforeach; ?>

            <?php $this->yieldSection('content'); ?>
        </main>
    </div>
</div>
<!-- MEDIA PICKER MODAL -->
<div id="media-picker-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; width:90%; max-width:800px; height:80vh; border-radius:12px; display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:15px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Medya Kütüphanesi</h3>
            <button onclick="closeMediaPicker()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <div id="media-picker-content" style="flex:1; overflow-y:auto; padding:20px; display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:15px; background:#f8fafc;">
            <!-- Images load here -->
        </div>
    </div>
</div>

<script>
let currentMediaCallback = null;

function openMediaPicker(callback) {
    currentMediaCallback = callback;
    const modal = document.getElementById('media-picker-modal');
    const content = document.getElementById('media-picker-content');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div style="grid-column:1/-1; text-align:center;">Yükleniyor...</div>';
    
    const mediaUrl = (window.GROO_ADMIN_URL || '/admin') + '/media/list';
    fetch(mediaUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.media.length > 0) {
                content.innerHTML = '';
                data.media.forEach(item => {
                    const div = document.createElement('div');
                    div.style.cssText = 'background:#fff; border:1px solid #e2e8f0; border-radius:8px; height:150px; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden;';
                    div.innerHTML = `<img src="${item.url}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
                    div.onclick = () => {
                        if (currentMediaCallback) currentMediaCallback(item.url);
                        closeMediaPicker();
                    };
                    content.appendChild(div);
                });
            } else {
                content.innerHTML = '<div style="grid-column:1/-1; text-align:center;">Görsel bulunamadı.</div>';
            }
        })
        .catch(err => {
            content.innerHTML = '<div style="grid-column:1/-1; text-align:center;">Hata oluştu.</div>';
        });
}

function closeMediaPicker() {
    document.getElementById('media-picker-modal').style.display = 'none';
    currentMediaCallback = null;
}
</script>

<script src="<?= $this->route('/admin/assets/js/admin.js') ?>"></script>
<?php $this->yieldSection('scripts'); ?>
</body>
</html>
