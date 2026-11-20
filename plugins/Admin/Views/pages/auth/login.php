<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş | <?= $this->e($_ENV['APP_NAME'] ?? 'Admin') ?></title>
    <link rel="stylesheet" href="<?= $this->route('/admin/assets/css/admin.css') ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <h1 class="login-brand">Groo<span>Admin</span></h1>
        <p class="login-subtitle">Yönetim paneline erişmek için giriş yapın.</p>

        <?php $flashError = \Plugins\Admin\Support\Flash::get('error'); ?>
        <?php if ($flashError): ?>
            <div class="alert alert-error"><?= $this->e($flashError) ?></div>
        <?php elseif ($this->data('failed')): ?>
            <div class="alert alert-error">E-posta veya parola hatalı.</div>
        <?php endif; ?>

        <form method="post" action="<?= $this->route('/admin/login') ?>" class="login-form">
            <?= $this->data('csrf_field') ?>
            <div class="form-row">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-row">
                <label for="password">Parola</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-row" style="flex-direction:row; align-items:center; gap:8px;">
                <input type="checkbox" id="remember" name="remember" value="1" style="width:auto; margin:0;">
                <label for="remember" style="margin:0; font-weight:normal; font-size:14px; cursor:pointer;">Beni Hatırla</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
