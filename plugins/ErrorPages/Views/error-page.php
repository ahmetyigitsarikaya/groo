<?php
/**
 * @var array $page
 * @var int $status
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($page['title']) ?> - Hata</title>
</head>
<body style="margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f8fafc;">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px;">
        <div style="max-width: 600px; text-align: center;">
            <?php if (!empty($page['image_url'])): ?>
                <img src="<?= $this->e($page['image_url']) ?>" alt="Error Illustration" style="max-width: 100%; height: auto; max-height: 300px; margin-bottom: 30px;">
            <?php else: ?>
                <div style="font-size: 100px; font-weight: 900; color: #cbd5e1; line-height: 1; margin-bottom: 20px;">
                    <?= $this->e($page['error_code']) ?>
                </div>
            <?php endif; ?>
            
            <h1 style="font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-bottom: 15px;">
                <?= $this->e($page['title']) ?>
            </h1>
            
            <?php if (!empty($page['message'])): ?>
                <p style="font-size: 1.1rem; color: #475569; margin-bottom: 30px; line-height: 1.6;">
                    <?= nl2br($this->e($page['message'])) ?>
                </p>
            <?php endif; ?>
            
            <a href="/" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background 0.2s;">
                Ana Sayfaya Dön
            </a>
        </div>
    </div>
</body>
</html>
