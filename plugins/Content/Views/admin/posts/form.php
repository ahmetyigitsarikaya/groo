<?php $this->extend('layouts/admin'); ?>

<?php
$post = $this->data('post');
$isEdit = (bool) $this->data('is_edit', false);
$action = $isEdit && isset($post['id'])
    ? $this->route('/admin/posts/' . (int) $post['id'] . '/edit')
    : $this->route('/admin/posts/create');

$contentBlocks = $post['content_blocks'] ?? [];
if (is_string($contentBlocks)) {
    $decoded = json_decode($contentBlocks, true);
    if (is_array($decoded)) {
        $contentBlocks = $decoded;
    }
}
?>

<?php $this->section('title'); ?><?= $isEdit ? 'Yazıyı Düzenle' : 'Yeni Yazı Ekle' ?><?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<style>
.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding: 6px 10px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-bottom: none;
    border-radius: 6px 6px 0 0;
    align-items: center;
}
.editor-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    color: #334155;
    transition: all 0.15s ease;
}
.editor-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.editor-btn.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.editor-content-area {
    min-height: 140px;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 0 0 6px 6px;
    background: #ffffff;
    font-family: inherit;
    font-size: 15px;
    line-height: 1.6;
    color: #1e293b;
    outline: none;
}
.editor-content-area:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
.block-card-wp {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    margin-bottom: 16px;
    overflow: hidden;
}
.block-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.block-card-title {
    font-weight: 700;
    font-size: 14px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.block-card-actions {
    display: flex;
    gap: 6px;
}
.block-card-body {
    padding: 14px;
}
.banner-preview-box {
    margin-top: 8px;
    max-height: 160px;
    border-radius: 8px;
    overflow: hidden;
    display: none;
    border: 1px solid #e2e8f0;
}
.banner-preview-box img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}
</style>

<div class="page-toolbar">
    <a class="btn btn-ghost" href="<?= $this->route('/admin/posts') ?>">&larr; Yazılara Dön</a>
</div>

<section class="panel">
    <form method="post" action="<?= $action ?>" class="admin-form" id="post-form">
        <?= $this->data('csrf_field') ?>

        <div class="form-row">
            <label for="title">Başlık <span class="muted">(zorunlu)</span></label>
            <input type="text" id="title" name="title" value="<?= $this->e($post['title'] ?? '') ?>" required placeholder="Yazı başlığını girin...">
        </div>

        <div class="form-row">
            <label for="slug">Slug <span class="muted">(boş bırakılırsa başlıktan otomatik üretilir)</span></label>
            <input type="text" id="slug" name="slug" value="<?= $this->e($post['slug'] ?? '') ?>" placeholder="ornek-yazi-slug">
        </div>

        <div class="form-row">
            <label for="excerpt">Özet (Excerpt)</label>
            <textarea id="excerpt" name="excerpt" rows="3" placeholder="Yazının kısa özeti..."><?= $this->e($post['excerpt'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <label for="banner_image">Görsel URL / Banner Image</label>
            <input type="text" id="banner_image" name="banner_image" value="<?= $this->e($post['banner_image'] ?? '') ?>" placeholder="https://... veya /assets/content/img/banner.jpg" oninput="updateBannerPreview(this.value)">
            <div id="banner-preview" class="banner-preview-box">
                <img id="banner-preview-img" src="" alt="Banner Önizleme">
            </div>
        </div>

        <div class="form-row">
            <label for="status">Durum</label>
            <select id="status" name="status">
                <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Yayınlandı</option>
            </select>
        </div>

        <div class="form-row">
            <label for="published_at">Yayın Tarihi</label>
            <input type="date" id="published_at" name="published_at" value="<?= $this->e($post['published_at'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="form-row">
            <label>İçerik Editörü (WordPress Tarzı Blok Yapısı)</label>
            <p class="muted" style="margin-top:0; margin-bottom:12px; font-size:13px;">
                Zengin metin düzenleyicisi ile kalın yazı, başlıklar, hizalama ve bağlantılar ekleyebilir; HTML moduna geçerek ham HTML kodu yazabilirsiniz.
            </p>

            <div id="block-list"></div>

            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0;">
                <button type="button" class="btn btn-primary" id="add-text-block-btn">+ Zengin Metin / HTML Bloğu</button>
                <button type="button" class="btn btn-outline" id="add-image-block-btn">+ Görsel Bloğu</button>
                <button type="button" class="btn btn-ghost" id="add-code-block-btn" style="border:1px dashed #cbd5e1;">+ Özel HTML Kodu</button>
            </div>

            <details style="margin-top:12px;">
                <summary style="font-size:12px; color:#64748b; cursor:pointer;">Ham JSON Verisi Göster/Gizle</summary>
                <textarea id="content_blocks" name="content_blocks" rows="5" class="mono" style="width:100%; font-family:monospace; font-size:12px; margin-top:8px;"><?= $this->e(is_array($contentBlocks) ? json_encode($contentBlocks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $contentBlocks) ?></textarea>
            </details>
        </div>

        <div class="form-actions" style="margin-top:24px;">
            <button type="submit" class="btn btn-primary" style="padding:12px 28px; font-size:1.05rem;"><?= $isEdit ? 'Yazıyı Güncelle' : 'Yazıyı Kaydet' ?></button>
        </div>
    </form>
</section>

<script>
function updateBannerPreview(url) {
    var box = document.getElementById('banner-preview');
    var img = document.getElementById('banner-preview-img');
    if (url && url.trim() !== '') {
        img.src = url;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var bannerInput = document.getElementById('banner_image');
    if (bannerInput && bannerInput.value) {
        updateBannerPreview(bannerInput.value);
    }

    var rawTextarea = document.getElementById('content_blocks');
    var blockListContainer = document.getElementById('block-list');
    var addTextBtn = document.getElementById('add-text-block-btn');
    var addImgBtn = document.getElementById('add-image-block-btn');
    var addCodeBtn = document.getElementById('add-code-block-btn');
    var postForm = document.getElementById('post-form');

    var blocks = [];
    try {
        blocks = JSON.parse(rawTextarea.value || '[]');
        if (!Array.isArray(blocks)) blocks = [];
    } catch(e) {
        blocks = [];
    }

    if (blocks.length === 0) {
        blocks.push({ type: 'text', content: '' });
    }

    function renderBlocks() {
        blockListContainer.innerHTML = '';
        blocks.forEach(function(block, index) {
            var card = document.createElement('div');
            card.className = 'block-card-wp';

            var header = document.createElement('div');
            header.className = 'block-card-header';

            var iconTitle = '📝 Metin & HTML Bloğu';
            if (block.type === 'image') iconTitle = '🖼️ Görsel Bloğu';
            if (block.type === 'html' || block.type === 'code') iconTitle = '💻 Özel HTML Bloğu';

            header.innerHTML = '<div class="block-card-title">' + iconTitle + '</div>';

            var actions = document.createElement('div');
            actions.className = 'block-card-actions';

            if (index > 0) {
                var upBtn = document.createElement('button');
                upBtn.type = 'button';
                upBtn.className = 'editor-btn';
                upBtn.innerHTML = '⬆️';
                upBtn.title = 'Yukarı Taşı';
                upBtn.onclick = function() {
                    syncCurrentEditors();
                    var temp = blocks[index];
                    blocks[index] = blocks[index - 1];
                    blocks[index - 1] = temp;
                    renderBlocks();
                };
                actions.appendChild(upBtn);
            }

            if (index < blocks.length - 1) {
                var downBtn = document.createElement('button');
                downBtn.type = 'button';
                downBtn.className = 'editor-btn';
                downBtn.innerHTML = '⬇️';
                downBtn.title = 'Aşağı Taşı';
                downBtn.onclick = function() {
                    syncCurrentEditors();
                    var temp = blocks[index];
                    blocks[index] = blocks[index + 1];
                    blocks[index + 1] = temp;
                    renderBlocks();
                };
                actions.appendChild(downBtn);
            }

            var delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'editor-btn';
            delBtn.style.color = '#ef4444';
            delBtn.innerHTML = '🗑️ Sil';
            delBtn.onclick = function() {
                syncCurrentEditors();
                blocks.splice(index, 1);
                renderBlocks();
            };
            actions.appendChild(delBtn);

            header.appendChild(actions);
            card.appendChild(header);

            var body = document.createElement('div');
            body.className = 'block-card-body';

            if (block.type === 'text' || block.type === 'wysiwyg') {
                var toolbar = document.createElement('div');
                toolbar.className = 'editor-toolbar';

                var tools = [
                    { label: '<b>B</b>', title: 'Kalın (Bold)', cmd: 'bold' },
                    { label: '<i>I</i>', title: 'İtalik (Italic)', cmd: 'italic' },
                    { label: '<u>U</u>', title: 'Altı Çizili', cmd: 'underline' },
                    { label: '<s>S</s>', title: 'Üstü Çizili', cmd: 'strikeThrough' },
                    { label: 'H2', title: 'Başlık 2', cmd: 'formatBlock', val: '<h2>' },
                    { label: 'H3', title: 'Başlık 3', cmd: 'formatBlock', val: '<h3>' },
                    { label: 'P', title: 'Paragraf', cmd: 'formatBlock', val: '<p>' },
                    { label: '⬅️', title: 'Sola Hizala', cmd: 'justifyLeft' },
                    { label: '↔️', title: 'Ortala', cmd: 'justifyCenter' },
                    { label: '➡️', title: 'Sağa Hizala', cmd: 'justifyRight' },
                    { label: '• Liste', title: 'Maddeli Liste', cmd: 'insertUnorderedList' },
                    { label: '1. Liste', title: 'Numaralı Liste', cmd: 'insertOrderedList' },
                    { label: '🔗 Link', title: 'Bağlantı Ekle', custom: 'link' },
                    { label: '🖼️ Görsel Yükle', title: 'Bilgisayardan Görsel Yükle', custom: 'upload' },
                    { label: '🧹 Temizle', title: 'Biçimlendirmeyi Temizle', cmd: 'removeFormat' }
                ];

                var htmlToggleBtn = document.createElement('button');
                htmlToggleBtn.type = 'button';
                htmlToggleBtn.className = 'editor-btn';
                htmlToggleBtn.style.marginLeft = 'auto';
                htmlToggleBtn.innerHTML = '💻 HTML Modu';

                var editorDiv = document.createElement('div');
                editorDiv.className = 'editor-content-area';
                editorDiv.contentEditable = true;
                editorDiv.innerHTML = block.content || '';

                var rawHtmlTextarea = document.createElement('textarea');
                rawHtmlTextarea.className = 'mono';
                rawHtmlTextarea.rows = 6;
                rawHtmlTextarea.style.width = '100%';
                rawHtmlTextarea.style.display = 'none';
                rawHtmlTextarea.style.fontFamily = 'monospace';
                rawHtmlTextarea.style.padding = '10px';
                rawHtmlTextarea.value = block.content || '';

                tools.forEach(function(t) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'editor-btn';
                    btn.innerHTML = t.label;
                    btn.title = t.title;
                    btn.onclick = function() {
                        editorDiv.focus();
                        if (t.custom === 'link') {
                            var url = prompt('Bağlantı URL girin:', 'https://');
                            if (url) document.execCommand('createLink', false, url);
                        } else if (t.custom === 'upload') {
                            var fileInput = document.createElement('input');
                            fileInput.type = 'file';
                            fileInput.accept = 'image/jpeg, image/png';
                            fileInput.onchange = function(e) {
                                var file = e.target.files[0];
                                if (file) uploadImageFile(file, editorDiv, rawHtmlTextarea, block);
                            };
                            fileInput.click();
                        } else if (t.val) {
                            document.execCommand(t.cmd, false, t.val);
                        } else {
                            document.execCommand(t.cmd, false, null);
                        }
                        block.content = editorDiv.innerHTML;
                        rawHtmlTextarea.value = editorDiv.innerHTML;
                        syncRawJSON();
                    };
                    toolbar.appendChild(btn);
                });

                var isHtmlMode = false;
                htmlToggleBtn.onclick = function() {
                    isHtmlMode = !isHtmlMode;
                    if (isHtmlMode) {
                        rawHtmlTextarea.value = editorDiv.innerHTML;
                        editorDiv.style.display = 'none';
                        rawHtmlTextarea.style.display = 'block';
                        htmlToggleBtn.classList.add('active');
                        htmlToggleBtn.innerHTML = '👁️ Görsel Mod';
                    } else {
                        editorDiv.innerHTML = rawHtmlTextarea.value;
                        rawHtmlTextarea.style.display = 'none';
                        editorDiv.style.display = 'block';
                        htmlToggleBtn.classList.remove('active');
                        htmlToggleBtn.innerHTML = '💻 HTML Modu';
                    }
                };
                toolbar.appendChild(htmlToggleBtn);

                // Sürükle-bırak desteği
                editorDiv.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    editorDiv.style.border = '2px dashed #4F46E5';
                });
                editorDiv.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    editorDiv.style.border = '1px solid #e2e8f0';
                });
                editorDiv.addEventListener('drop', function(e) {
                    e.preventDefault();
                    editorDiv.style.border = '1px solid #e2e8f0';
                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        var file = e.dataTransfer.files[0];
                        uploadImageFile(file, editorDiv, rawHtmlTextarea, block);
                    }
                });

                editorDiv.oninput = function() {
                    block.content = editorDiv.innerHTML;
                    rawHtmlTextarea.value = editorDiv.innerHTML;
                    syncRawJSON();
                };

                rawHtmlTextarea.oninput = function() {
                    block.content = rawHtmlTextarea.value;
                    editorDiv.innerHTML = rawHtmlTextarea.value;
                    syncRawJSON();
                };

                body.appendChild(toolbar);
                body.appendChild(editorDiv);
                body.appendChild(rawHtmlTextarea);
            } else if (block.type === 'image') {
                var gridDiv = document.createElement('div');
                gridDiv.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr; gap:12px;';

                gridDiv.innerHTML = `
                    <div style="grid-column: 1 / -1; display:flex; gap:10px; align-items:flex-end;">
                        <div style="flex:1;">
                            <label style="font-size:12px; font-weight:600;">Görsel URL (Zorunlu)</label>
                            <input type="text" class="img-url-input" value="${escapeHtml(block.url || block.src || '')}" placeholder="https://... veya /assets/img.jpg" style="width:100%;">
                        </div>
                        <button type="button" class="btn btn-secondary media-pick-btn" style="padding:6px 12px;">🖼️ Kütüphaneden Seç</button>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600;">Alt Metin (Alt Text)</label>
                        <input type="text" class="img-alt-input" value="${escapeHtml(block.alt || '')}" placeholder="Görsel açıklaması..." style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600;">Hizalama</label>
                        <select class="img-align-select" style="width:100%;">
                            <option value="center" ${(block.align || 'center') === 'center' ? 'selected' : ''}>Ortala</option>
                            <option value="left" ${(block.align || '') === 'left' ? 'selected' : ''}>Sola Hizala</option>
                            <option value="right" ${(block.align || '') === 'right' ? 'selected' : ''}>Sağa Hizala</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="font-size:12px; font-weight:600;">Görsel Altyazısı (Caption)</label>
                        <input type="text" class="img-caption-input" value="${escapeHtml(block.caption || '')}" placeholder="Görsel altında görünecek altyazı..." style="width:100%;">
                    </div>
                `;

                var imgUrlInput = gridDiv.querySelector('.img-url-input');
                var imgAltInput = gridDiv.querySelector('.img-alt-input');
                var imgAlignSelect = gridDiv.querySelector('.img-align-select');
                var imgCaptionInput = gridDiv.querySelector('.img-caption-input');
                var mediaPickBtn = gridDiv.querySelector('.media-pick-btn');

                var updateImageBlock = function() {
                    block.url = imgUrlInput.value;
                    block.alt = imgAltInput.value;
                    block.align = imgAlignSelect.value;
                    block.caption = imgCaptionInput.value;
                    syncRawJSON();
                };

                mediaPickBtn.onclick = function() {
                    if (window.openMediaPicker) {
                        window.openMediaPicker(function(url) {
                            imgUrlInput.value = url;
                            updateImageBlock();
                        });
                    } else {
                        alert('Medya kütüphanesi yüklenemedi.');
                    }
                };

                imgUrlInput.oninput = updateImageBlock;
                imgAltInput.oninput = updateImageBlock;
                imgAlignSelect.onchange = updateImageBlock;
                imgCaptionInput.oninput = updateImageBlock;

                body.appendChild(gridDiv);
            } else if (block.type === 'html' || block.type === 'code') {
                var codeArea = document.createElement('textarea');
                codeArea.className = 'mono';
                codeArea.rows = 6;
                codeArea.style.width = '100%';
                codeArea.style.fontFamily = 'monospace';
                codeArea.style.padding = '10px';
                codeArea.value = block.content || '';
                codeArea.placeholder = '<div class="custom-embed">...</div>';

                codeArea.oninput = function() {
                    block.content = codeArea.value;
                    syncRawJSON();
                };

                body.appendChild(codeArea);
            }

            card.appendChild(body);
            blockListContainer.appendChild(card);
        });

        syncRawJSON();
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function uploadImageFile(file, editorDiv, rawArea, blockObj) {
        var formData = new FormData();
        formData.append('file', file);
        
        var uploadIndicator = document.createElement('span');
        uploadIndicator.innerText = ' [Görsel Yükleniyor...] ';
        editorDiv.appendChild(uploadIndicator);
        
        var uploadUrl = (window.GROO_ADMIN_URL || '/admin') + '/media/upload';
        fetch(uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            editorDiv.removeChild(uploadIndicator);
            if (data.success) {
                var widthAttr = data.width ? `width="${data.width}"` : '';
                var heightAttr = data.height ? `height="${data.height}"` : '';
                var imgHtml = `<img src="${data.url}" ${widthAttr} ${heightAttr} loading="lazy" decoding="async" style="max-width:100%; height:auto;" />`;
                
                editorDiv.focus();
                document.execCommand('insertHTML', false, imgHtml);
                
                blockObj.content = editorDiv.innerHTML;
                rawArea.value = editorDiv.innerHTML;
                syncRawJSON();
            } else {
                alert('Yükleme başarısız: ' + data.message);
            }
        })
        .catch(err => {
            editorDiv.removeChild(uploadIndicator);
            alert('Yükleme hatası oluştu!');
            console.error(err);
        });
    }

    function syncCurrentEditors() {
        var cardBodies = blockListContainer.querySelectorAll('.block-card-wp');
        cardBodies.forEach(function(card, index) {
            if (!blocks[index]) return;
            var editorDiv = card.querySelector('.editor-content-area');
            var rawArea = card.querySelector('textarea.mono');
            if (editorDiv) {
                blocks[index].content = editorDiv.innerHTML;
            } else if (rawArea && (blocks[index].type === 'html' || blocks[index].type === 'code')) {
                blocks[index].content = rawArea.value;
            }
        });
    }

    function syncRawJSON() {
        rawTextarea.value = JSON.stringify(blocks, null, 2);
    }

    addTextBtn.onclick = function() {
        syncCurrentEditors();
        blocks.push({ type: 'text', content: '' });
        renderBlocks();
    };

    addImgBtn.onclick = function() {
        syncCurrentEditors();
        blocks.push({ type: 'image', url: '', alt: '', caption: '', align: 'center' });
        renderBlocks();
    };

    addCodeBtn.onclick = function() {
        syncCurrentEditors();
        blocks.push({ type: 'html', content: '' });
        renderBlocks();
    };

    rawTextarea.addEventListener('change', function() {
        try {
            var parsed = JSON.parse(rawTextarea.value);
            if (Array.isArray(parsed)) {
                blocks = parsed;
                renderBlocks();
            }
        } catch(e) {}
    });

    postForm.addEventListener('submit', function() {
        syncCurrentEditors();
        syncRawJSON();
    });

    renderBlocks();
});
</script>
<?php $this->endSection(); ?>
