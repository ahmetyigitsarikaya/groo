<?php
/**
 * @var array $metas
 * @var array $settings
 * @var \Plugins\Admin\Models\AdminUser $admin_user
 * @var array $admin_permissions
 * @var array $menu
 * @var array $flash
 * @var string $csrf_field
 */
?>
<?php $this->extend('layouts/admin'); ?>
<?php $this->section('title'); ?>SEO Yönetimi<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<?php
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = (string) \App\Core\Config::get('app.base_path', '');
$siteUrl = rtrim($scheme . '://' . $host . $basePath, '/');
?>

<div class="admin-header">
    <div class="header-content">
        <h1>SEO Engine</h1>
        <p>Sitenizin arama motorlarındaki görünümünü ve davranışını yönetin.</p>
    </div>
    <div class="header-actions">
        <form method="POST" action="<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('/seo/clear-cache')) ?>" style="display:inline;">
            <?= $csrf_field ?>
            <button type="submit" class="btn btn-outline">
                <i class="icon-refresh"></i> Önbelleği Temizle
            </button>
        </form>
    </div>
</div>

<div class="seo-tabs">
    <button class="seo-tab active" data-target="tab-pages">Sayfa Bazlı SEO</button>
    <button class="seo-tab" data-target="tab-general">Genel SEO & Doğrulama</button>
    <button class="seo-tab" data-target="tab-robots">Robots.txt & Sitemap</button>
</div>

<div class="seo-content">
    
    <!-- SEKME 1: SAYFA BAZLI SEO -->
    <div class="seo-pane active" id="tab-pages">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Yeni SEO Kuralı Ekle</div>
                    <div class="card-body">
                        <form method="POST" action="<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('/seo/meta')) ?>">
                            <?= $csrf_field ?>
                            
                            <div class="form-group">
                                <label>URL Rotası (Pattern) <span class="text-danger">*</span></label>
                                <input type="text" name="url_pattern" class="form-control" placeholder="Örn: /hakkimizda veya /blog/*" required>
                                <small>Kök sayfa için <b>/</b> giriniz.</small>
                            </div>

                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" name="title" class="form-control" id="seo_title_input" maxlength="60">
                            </div>

                            <div class="form-group">
                                <label>Meta Description</label>
                                <textarea name="description" class="form-control" rows="3" id="seo_desc_input" maxlength="160"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Anahtar Kelimeler (Virgülle ayırın)</label>
                                <input type="text" name="keywords" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Schema.org Tipi</label>
                                <select name="schema_type" class="form-control">
                                    <option value="">(Seçiniz)</option>
                                    <option value="WebPage">WebPage</option>
                                    <option value="Article">Article</option>
                                    <option value="LocalBusiness">LocalBusiness</option>
                                    <option value="Product">Product</option>
                                </select>
                            </div>

                            <div class="form-group d-flex gap-3 mt-3">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" name="noindex" value="1"> NoIndex (İndekslenmesin)
                                </label>
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" name="nofollow" value="1"> NoFollow (Linkler takip edilmesin)
                                </label>
                            </div>

                            <hr>
                            <h5>Open Graph (Sosyal Medya)</h5>
                            
                            <div class="form-group">
                                <label>OG Title</label>
                                <input type="text" name="og_title" class="form-control" placeholder="Boş bırakılırsa Meta Title kullanılır">
                            </div>
                            
                            <div class="form-group">
                                <label>OG Description</label>
                                <textarea name="og_description" class="form-control" rows="2" placeholder="Boş bırakılırsa Meta Description kullanılır"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>OG Görsel URL</label>
                                <input type="url" name="og_image" class="form-control" placeholder="https://...">
                            </div>

                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-primary w-100">Kuralı Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <!-- SERP Preview -->
                <div class="card mb-4">
                    <div class="card-header">Canlı Arama Sonucu Önizlemesi (SERP Preview)</div>
                    <div class="card-body">
                        <div class="serp-preview">
                            <div class="serp-url"><?= htmlspecialchars($siteUrl) ?><span id="serp_url_preview">/ornek-sayfa</span></div>
                            <div class="serp-title" id="serp_title_preview">Yeni Bir Sayfa Başlığı Girin</div>
                            <div class="serp-desc" id="serp_desc_preview">Meta description girilmediğinde Google sayfa içerisinden ilgili gördüğü metni alarak burada gösterecektir. Buraya açıklayıcı ve dikkat çekici bir metin girmelisiniz.</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Kayıtlı Rotalar</div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>URL Pattern</th>
                                    <th>Title</th>
                                    <th>İndeks</th>
                                    <th width="100">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($metas)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Henüz hiç özel SEO kuralı eklenmemiş.</td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php foreach($metas as $meta): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($meta['url_pattern']) ?></code></td>
                                    <td><?= htmlspecialchars($meta['title'] ?? '-') ?></td>
                                    <td>
                                        <?php if($meta['noindex']): ?>
                                            <span class="badge badge-danger">NoIndex</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Index</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('/seo/meta/' . $meta['id'] . '/delete')) ?>" onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?');">
                                            <?= $csrf_field ?>
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="icon-trash"></i></button>
                                        </form>
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

    <!-- SEKME 2: GENEL SEO VE DOĞRULAMA -->
    <div class="seo-pane" id="tab-general">
        <div class="card">
            <div class="card-header">Site Geneli SEO Ayarları</div>
            <div class="card-body">
                <form method="POST" action="<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('/seo/settings')) ?>">
                    <?= $csrf_field ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Başlık Ayırıcı Karakter</label>
                                <select name="separator" class="form-control">
                                    <option value="|" <?= ($settings['separator'] ?? '') === '|' ? 'selected' : '' ?>>| (Dikey Çizgi)</option>
                                    <option value="-" <?= ($settings['separator'] ?? '') === '-' ? 'selected' : '' ?>>- (Tire)</option>
                                    <option value="&raquo;" <?= ($settings['separator'] ?? '') === '&raquo;' ? 'selected' : '' ?>>&raquo; (Çift Ok)</option>
                                </select>
                                <small>Örn: Hakkımızda | Site Adı</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Varsayılan Open Graph (OG) Görseli</label>
                                <input type="url" name="default_og_image" class="form-control" value="<?= htmlspecialchars($settings['default_og_image'] ?? '') ?>" placeholder="https://domain.com/assets/img/default-social.jpg">
                                <small>Sayfa bazlı özel resim girilmediğinde sosyal medyada bu resim kullanılır.</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Google Site Verification Code</label>
                                <input type="text" name="google_site_verification" class="form-control" value="<?= htmlspecialchars($settings['google_site_verification'] ?? '') ?>" placeholder="Kod giriniz">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bing Site Verification Code</label>
                                <input type="text" name="bing_site_verification" class="form-control" value="<?= htmlspecialchars($settings['bing_site_verification'] ?? '') ?>" placeholder="Kod giriniz">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SEKME 3: ROBOTS VE SITEMAP -->
    <div class="seo-pane" id="tab-robots">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Robots.txt Düzenleyici</div>
                    <div class="card-body">
                        <form method="POST" action="<?= $this->route(\Plugins\Admin\Support\AdminPaths::path('/seo/robots')) ?>">
                            <?= $csrf_field ?>
                            
                            <div class="form-group">
                                <label>Robots.txt İçeriği</label>
                                <textarea name="robots_txt_content" class="form-control code-editor" rows="10"><?= htmlspecialchars($settings['robots_txt_content'] ?? '') ?></textarea>
                                <small>Sitemap.xml rotası otomatik olarak en alta eklenecektir, buraya eklemenize gerek yoktur.</small>
                            </div>
                            
                            <div class="form-actions mt-3">
                                <button type="submit" class="btn btn-primary">Robots.txt Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">XML Sitemap Durumu</div>
                    <div class="card-body text-center py-5">
                        <i class="icon-map text-primary" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Dinamik Sitemap Aktif</h3>
                        <p class="text-muted">Sitemap, sitenizdeki aktif sayfaları (NoIndex olanlar hariç) tarayarak anlık olarak XML çıktısı üretir. Performans için 6 saatte bir cache'lenir.</p>
                        
                        <div class="mt-4">
                            <a href="<?= htmlspecialchars($siteUrl) ?>/sitemap.xml" target="_blank" class="btn btn-outline">
                                <i class="icon-external-link"></i> sitemap.xml'i Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern SEO Tabs */
.seo-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: #f8fafc;
    padding: 0.5rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.seo-tab {
    background: transparent;
    border: none;
    padding: 0.85rem 1.5rem;
    font-size: 0.95rem;
    color: #64748b;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.seo-tab:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.seo-tab.active {
    color: var(--primary-color, #4f46e5);
    background: #fff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
}
.seo-pane {
    display: none;
    animation: slideFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.seo-pane.active {
    display: block;
}

@keyframes slideFadeIn {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Beautiful Header */
.admin-header {
    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
    border: 1px solid #e0e7ff;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
}
.admin-header h1 {
    font-size: 1.75rem;
    color: #1e293b;
    margin-bottom: 0.25rem;
    font-weight: 700;
}
.admin-header p {
    color: #64748b;
    font-size: 0.95rem;
    margin: 0;
}

/* Card Enhancements are moved to admin.css */

/* SERP Preview Refined */
.serp-preview {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s ease;
}
.serp-preview:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.serp-url {
    color: var(--text);
    font-size: 14px;
    line-height: 1.3;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.serp-url::before {
    content: '';
    display: inline-block;
    width: 24px;
    height: 24px;
    background: var(--border);
    border-radius: 50%;
}
.serp-title {
    color: #1a0dab;
    font-size: 22px;
    line-height: 1.3;
    font-family: Arial, sans-serif;
    margin-bottom: 6px;
    cursor: pointer;
}
.serp-title:hover {
    text-decoration: underline;
}
.serp-desc {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.58;
    word-wrap: break-word;
}

/* Form Polish */
.form-control {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.65rem 1rem;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
    outline: none;
}
label {
    font-weight: 500;
    color: #334155;
    margin-bottom: 0.4rem;
    display: block;
}

/* Code Editor */
.code-editor {
    font-family: 'Consolas', 'Monaco', monospace;
    background: #1e293b;
    color: #e2e8f0;
    border: none;
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    line-height: 1.5;
}
.code-editor:focus {
    box-shadow: 0 0 0 3px rgba(30, 41, 59, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tabs
    const tabs = document.querySelectorAll('.seo-tab');
    const panes = document.querySelectorAll('.seo-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(tab.dataset.target).classList.add('active');
        });
    });

    // SERP Preview Sync
    const titleInput = document.getElementById('seo_title_input');
    const descInput = document.getElementById('seo_desc_input');
    const urlInput = document.querySelector('input[name="url_pattern"]');
    
    const titlePreview = document.getElementById('serp_title_preview');
    const descPreview = document.getElementById('serp_desc_preview');
    const urlPreview = document.getElementById('serp_url_preview');

    const updatePreview = () => {
        titlePreview.textContent = titleInput.value.trim() || 'Yeni Bir Sayfa Başlığı Girin';
        descPreview.textContent = descInput.value.trim() || 'Meta description girilmediğinde Google sayfa içerisinden ilgili gördüğü metni alarak burada gösterecektir. Buraya açıklayıcı ve dikkat çekici bir metin girmelisiniz.';
        
        let path = urlInput.value.trim();
        if(path === '/' || path === '') {
            urlPreview.textContent = '';
        } else {
            urlPreview.textContent = path.startsWith('/') ? path : '/' + path;
        }
    };

    if(titleInput && descInput && urlInput) {
        titleInput.addEventListener('input', updatePreview);
        descInput.addEventListener('input', updatePreview);
        urlInput.addEventListener('input', updatePreview);
    }
});
</script>
<?php $this->endSection(); ?>
