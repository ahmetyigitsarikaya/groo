<?php
// Mevcut URL'yi al (aktif link için)
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePath    = rtrim((string) (\App\Core\Config::get('app.base_path') ?? ''), '/');
$adminUser   = $this->data('admin_user', []);
$userName    = $adminUser['name'] ?? $adminUser['email'] ?? 'Admin';
$userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
$userRole    = in_array('*', $adminUser['permissions'] ?? []) ? 'Super Admin' : ($adminUser['role'] ?? 'Yönetici');

function sidebarActive(string $route, string $current, string $base, bool $exact = false): string
{
    $full = rtrim($base . $route, '/');
    if ($exact) {
        return (rtrim($current, '/') === $full) ? ' active' : '';
    }
    return (rtrim($current, '/') === $full || str_starts_with($current, $full . '/')) ? ' active' : '';
}
?>
<aside class="admin-sidebar">
    <a class="sidebar-brand" href="<?= $this->route('/admin') ?>">
        <?= $this->e($_ENV['APP_NAME'] ?? 'Groo') ?><span>Admin</span>
    </a>

    <nav class="sidebar-nav">
        <a class="sidebar-link<?= sidebarActive('/admin', $currentPath, $basePath, true) ?>" href="<?= $this->route('/admin') ?>">
            <span class="menu-icon">🏠</span>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-label">İçerik</div>

        <a class="sidebar-link<?= sidebarActive('/admin/posts', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/posts') ?>">
            <span class="menu-icon">📄</span>
            <span>Blog Yazıları</span>
        </a>
        
        <a class="sidebar-link<?= sidebarActive('/admin/media', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/media') ?>">
            <span class="menu-icon">🖼️</span>
            <span>Medya Kütüphanesi</span>
        </a>

        <div class="sidebar-section-label">CRM</div>

        <a class="sidebar-link<?= sidebarActive('/admin/crm/contacts', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/crm/contacts') ?>">
            <span class="menu-icon">👥</span>
            <span>Müşteriler</span>
        </a>
        <a class="sidebar-link<?= sidebarActive('/admin/crm/tasks', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/crm/tasks') ?>">
            <span class="menu-icon">📅</span>
            <span>Görevler & Ajanda</span>
        </a>
        <a class="sidebar-link<?= sidebarActive('/admin/crm/finance', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/crm/finance') ?>">
            <span class="menu-icon">💳</span>
            <span>Ön Muhasebe</span>
        </a>

        <div class="sidebar-section-label">Analiz & SEO</div>

        <a class="sidebar-link<?= sidebarActive('/admin/traffic', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/traffic') ?>">
            <span class="menu-icon">📊</span>
            <span>Trafik</span>
        </a>

        <a class="sidebar-link<?= sidebarActive('/admin/seo', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/seo') ?>">
            <span class="menu-icon">🔍</span>
            <span>SEO</span>
        </a>

        <div class="sidebar-section-label">Sistem</div>
        
        <a class="sidebar-link<?= sidebarActive('/admin/users', $currentPath, $basePath, true) ?>" href="<?= $this->route('/admin/users') ?>">
            <span class="menu-icon">👤</span>
            <span>Kullanıcılar</span>
        </a>
        <a class="sidebar-link<?= sidebarActive('/admin/users/roles', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/users/roles') ?>">
            <span class="menu-icon">🛡️</span>
            <span>Roller ve İzinler</span>
        </a>
        <a class="sidebar-link<?= sidebarActive('/admin/users/admins', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/users/admins') ?>">
            <span class="menu-icon">🔒</span>
            <span>Admin Hesapları</span>
        </a>

        <a class="sidebar-link<?= sidebarActive('/admin/plugins', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/plugins') ?>">
            <span class="menu-icon">🔌</span>
            <span>Eklentiler</span>
        </a>

        <a class="sidebar-link<?= sidebarActive('/admin/themes', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/themes') ?>">
            <span class="menu-icon">🎨</span>
            <span>Temalar</span>
        </a>

        <a class="sidebar-link<?= sidebarActive('/admin/contact-messages', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/contact-messages') ?>">
            <span class="menu-icon">✉️</span>
            <span>Gelen Mesajlar</span>
        </a>

        <a class="sidebar-link<?= sidebarActive('/admin/error-pages', $currentPath, $basePath) ?>" href="<?= $this->route('/admin/error-pages') ?>">
            <span class="menu-icon">⚠️</span>
            <span>Hata Sayfaları</span>
        </a>
    </nav>

    <!-- Kullanıcı bilgisi en altta -->
    <div class="sidebar-footer">
        <div class="sidebar-user-avatar"><?= $this->e($userInitial) ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= $this->e($userName) ?></div>
            <div class="sidebar-user-role"><?= $this->e($userRole) ?></div>
        </div>
        <form method="post" action="<?= $this->route('/admin/logout') ?>" style="margin:0;">
            <?= $this->data('csrf_field') ?>
            <button type="submit" class="sidebar-logout-btn" title="Çıkış Yap">⏻</button>
        </form>
    </div>
</aside>
