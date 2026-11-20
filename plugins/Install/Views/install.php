<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Sistem Kurulumu</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f3f4f6; color: #1f2937; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 32px; text-align: center; }
        h1 { margin-top: 0; font-size: 24px; }
        p { color: #4b5563; }
        .btn { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 16px; margin-top: 16px; }
        .btn:hover { background: #4338ca; }
        .log { text-align: left; background: #1f2937; color: #10b981; font-family: monospace; padding: 16px; border-radius: 6px; margin-top: 24px; max-height: 200px; overflow-y: auto; font-size: 12px; }
        .alert { padding: 12px; border-radius: 6px; margin-top: 16px; text-align: left; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Groo CMS Kurulumu</h1>
        <?php if (!isset($done)): ?>
            <p>Veritabanı tablolarını ve başlangıç verilerini kurmak için aşağıdaki butona tıklayın. Sistem zaten kuruluysa, eksik tablolar eklenecektir.</p>
            <form method="post">
                <button type="submit" class="btn">Kurulumu Başlat</button>
            </form>
        <?php else: ?>
            <?php if ($success): ?>
                <div class="alert success"><strong>Başarılı!</strong> Tüm migrasyonlar başarıyla çalıştırıldı.</div>
            <?php else: ?>
                <div class="alert error"><strong>Hata!</strong> Kurulum sırasında bir sorun oluştu. Detayları logda bulabilirsiniz.</div>
            <?php endif; ?>
            
            <div class="log">
                <?php foreach ($log as $l): ?>
                    <div><?= htmlspecialchars($l) ?></div>
                <?php endforeach; ?>
            </div>

            <?php if ($success): ?>
                <a href="<?= \App\Core\Config::get('app.base_path') ?>/admin" class="btn">Admin Paneline Git</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
