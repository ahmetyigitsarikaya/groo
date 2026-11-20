<?php $this->extend('layouts/admin'); ?>

<?php $this->section('title'); ?>Medya Kütüphanesi<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="page-toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
    <div>
        <p class="muted" style="margin: 0;">Yüklenen tüm görselleri buradan yönetebilirsiniz. Yeni görsel yüklemek için masaüstünden resim seçebilir veya içerik düzenleyicileri kullanabilirsiniz.</p>
    </div>
    <div>
        <form action="<?= $this->route('/admin/media/optimize-theme-assets') ?>" method="POST" onsubmit="return confirm('Bu işlem, tema klasöründeki tüm orijinal JPG ve PNG dosyalarını bulup yanlarına WebP kopyalarını oluşturacaktır. Devam etmek istiyor musunuz?');">
            <?= $this->data('csrf_field') ?>
            <button type="submit" class="btn btn-primary">🛠️ Temayı Optimize Et (WebP)</button>
        </form>
    </div>
</div>

<section class="panel">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
        <?php $media = $this->data('media', []); ?>
        <?php if(empty($media)): ?>
            <p class="muted">Henüz hiçbir görsel yüklenmemiş.</p>
        <?php else: ?>
            <?php foreach($media as $item): ?>
                <div class="media-card" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div class="media-preview" style="height: 150px; background: #f8fafc; display: flex; align-items: center; justify-content: center; padding: 8px; border-bottom: 1px solid #f1f5f9;">
                        <img src="<?= $this->e($item['url']) ?>" alt="Media" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>
                    <div class="media-info" style="padding: 12px; font-size: 12px;">
                        <div style="font-weight: 600; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= $this->e($item['filename']) ?>"><?= $this->e($item['filename']) ?></div>
                        <input type="text" readonly value="<?= $this->e($item['url']) ?>" style="width: 100%; padding: 4px 8px; margin-bottom: 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;" onclick="this.select(); document.execCommand('copy'); alert('Görsel adresi kopyalandı!');" title="Tıklayıp adresi kopyalayın">
                        <div style="display: flex; justify-content: space-between; color: #64748b; font-size: 11px; margin-bottom: 10px;">
                            <span><?= $item['width'] && $item['height'] ? $item['width'] . 'x' . $item['height'] : 'Bilinmiyor' ?></span>
                            <span><?= round(($item['size_bytes'] ?? 0) / 1024) ?> KB</span>
                        </div>
                        <form action="<?= $this->route('/admin/media/delete') ?>" method="POST" onsubmit="return confirm('Bu görseli silmek istediğinize emin misiniz?');">
                            <?= $this->data('csrf_field') ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="width: 100%; color: #ef4444; border-color: #fca5a5; padding: 4px;">Sil</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php $this->endSection(); ?>
