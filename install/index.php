<?php

declare(strict_types=1);

/**
 * Groo Kurulum Sihirbazı
 *
 * MVC çekirdeğinden bağımsız çalışır. Kurulum tamamlandıktan sonra
 * production ortamlarında kendini siler.
 * PHP >= 8.0 (readonly/enum kullanılmaz)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('GROO_BASE', dirname(__DIR__));
define('GROO_ENV_FILE', GROO_BASE . DIRECTORY_SEPARATOR . '.env');
define('GROO_INSTALL_DIR', __DIR__);

session_start();

/* ------------------------------------------------------------------ */
/* Yardımcı Fonksiyonlar                                              */
/* ------------------------------------------------------------------ */

function groo_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function groo_read_env(): array
{
    if (!is_file(GROO_ENV_FILE)) {
        return [];
    }

    $values = [];
    $lines = file(GROO_ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value);
    }

    return $values;
}

function groo_update_env(array $updates): bool
{
    $lines = is_file(GROO_ENV_FILE) ? file(GROO_ENV_FILE, FILE_IGNORE_NEW_LINES) : [];
    if ($lines === false) {
        $lines = [];
    }

    $written = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '' && $trimmed[0] !== '#' && strpos($trimmed, '=') !== false) {
            $key = trim(explode('=', $trimmed, 2)[0]);
            if (array_key_exists($key, $updates)) {
                $written[] = $key . '=' . $updates[$key];
                unset($updates[$key]);
                continue;
            }
        }
        $written[] = $line;
    }

    foreach ($updates as $key => $value) {
        $written[] = $key . '=' . $value;
    }

    return @file_put_contents(GROO_ENV_FILE, implode(PHP_EOL, $written) . PHP_EOL, LOCK_EX) !== false;
}

function groo_base_path(): string
{
    $env = groo_read_env();
    $configured = trim($env['APP_BASE_PATH'] ?? '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname(dirname($script));

    return ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/');
}

function groo_install_url(int $step): string
{
    return groo_base_path() . '/install/index.php?step=' . $step;
}

function groo_sanitize_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug === '' ? 'admin' : $slug;
}

function groo_delete_directory(string $dir, array $preserve = []): bool
{
    try {
        if (!is_dir($dir)) {
            return true;
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, $preserve, true)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                if (!groo_delete_directory($path)) {
                    return false;
                }
                continue;
            }

            try {
                if (!@unlink($path) && is_file($path)) {
                    return false;
                }
            } catch (Throwable $e) {
                return false;
            }
        }

        if (empty($preserve)) {
            try {
                if (!@rmdir($dir) && is_dir($dir)) {
                    return false;
                }
            } catch (Throwable $e) {
                return false;
            }
        }

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function groo_db_connect(string $host, string $port, string $database, string $username, string $password): PDO
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
            $options
        );
    } catch (PDOException $e) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
                $username,
                $password,
                $options
            );

            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $database) ?? '';
            if ($safe === '') {
                throw new PDOException('Veritabanı adı geçersiz.');
            }

            $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $safe));
            $pdo->exec(sprintf('USE `%s`', $safe));

            return $pdo;
        } catch (PDOException $e2) {
            $debugMsg = sprintf("Host: %s, DB: %s, User: %s", $host, $database, $username);
            throw new PDOException($e2->getMessage() . " | [DEBUG: " . $debugMsg . "]");
        }
    }
}

function groo_run_migrations(PDO $pdo): array
{
    $autoload = GROO_BASE . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('vendor/autoload.php bulunamadı. Lütfen önce "composer install" çalıştırın.');
    }
    require_once $autoload;

    $manager = new App\Core\Database\MigrationManager($pdo);
    $log = [];

    $migrationsPath = GROO_INSTALL_DIR . DIRECTORY_SEPARATOR . 'migrations';
    if (is_dir($migrationsPath)) {
        foreach ($manager->run('install', $migrationsPath) as $name) {
            $log[] = $name;
        }
    }

    return $log;
}

function groo_csrf_token(): string
{
    if (empty($_SESSION['groo_install_token'])) {
        $_SESSION['groo_install_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['groo_install_token'];
}

function groo_csrf_check(): bool
{
    $token = $_SESSION['groo_install_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($token, (string) ($_POST['_token'] ?? ''));
}

/* ------------------------------------------------------------------ */
/* Arayüz Fonksiyonları                                               */
/* ------------------------------------------------------------------ */

function groo_page(string $title, int $activeStep, string $content): void
{
    $steps = [1 => 'Gereksinimler', 2 => 'Veritabanı', 3 => 'Ayarlar', 4 => 'Tamamlanma'];
    $indicator = '';

    foreach ($steps as $number => $label) {
        $state = $number < $activeStep
            ? 'bg-emerald-500 text-white'
            : ($number === $activeStep ? 'bg-blue-900 text-white' : 'bg-gray-200 text-gray-500');
        
        $indicator .= sprintf(
            '<div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold %s">%d</span>
                <span class="text-sm font-semibold hidden md:inline-block %s">%s</span>
            </div>',
            $state,
            $number,
            $number === $activeStep ? 'text-blue-900' : 'text-gray-500',
            groo_e($label)
        );

        if ($number < 4) {
            $indicator .= '<div class="w-6 md:w-12 h-px bg-gray-300"></div>';
        }
    }

    echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . groo_e($title) . ' · Groo Kurulum</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col items-center justify-center px-4 py-10 font-sans">
    <div class="w-full max-w-3xl">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-black tracking-tight text-slate-900">Groo<span class="text-indigo-600">.</span></h1>
            <p class="text-slate-500 mt-2 font-medium">Modern İçerik Yönetim Sistemi</p>
        </div>
        <div class="flex items-center justify-center mb-10">' . $indicator . '</div>
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 md:p-10">' . $content . '</div>
        <p class="text-center text-xs text-slate-400 mt-8 font-medium">Groo Kurulum Sihirbazı · PHP ' . groo_e(PHP_VERSION) . '</p>
    </div>
</body>
</html>';
}

function groo_alert(string $type, string $message): string
{
    $styles = $type === 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-emerald-50 border-emerald-200 text-emerald-700';

    return sprintf('<div class="border rounded-xl px-5 py-4 mb-8 text-sm font-medium %s">%s</div>', $styles, $message);
}

function groo_field(string $label, string $name, string $value, string $type = 'text', string $hint = '', bool $required = true): string
{
    $req = $required ? ' required' : '';

    return sprintf(
        '<label class="block mb-5">
            <span class="block text-sm font-semibold text-slate-700 mb-2">%s</span>
            <input type="%s" name="%s" value="%s"%s
                   class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition-shadow">
            %s
        </label>',
        groo_e($label),
        groo_e($type),
        groo_e($name),
        groo_e($value),
        $req,
        $hint !== '' ? sprintf('<span class="block text-xs text-slate-400 mt-2 font-medium">%s</span>', groo_e($hint)) : ''
    );
}

/* ------------------------------------------------------------------ */
/* Adım 1: Sistem Gereksinimleri ve Temizlik                          */
/* ------------------------------------------------------------------ */

function groo_step1_check(): array
{
    $errors = [];

    // 1. PHP Sürümü
    if (PHP_VERSION_ID < 80000) {
        $errors[] = 'PHP sürümünüz ' . PHP_VERSION . '. Groo, PHP 8.0 ve üzeri gerektirir.';
    }

    // 2. PDO Eklentisi
    if (!extension_loaded('pdo_mysql')) {
        $errors[] = 'PDO MySQL eklentisi (pdo_mysql) yüklü değil veya aktif değil.';
    }

    // 3. Dosya İzinleri
    if (!is_writable(GROO_BASE)) {
        $errors[] = 'Kök dizine (' . GROO_BASE . ') yazma izni yok.';
    }
    if (is_file(GROO_ENV_FILE) && !is_writable(GROO_ENV_FILE)) {
        $errors[] = '.env dosyasına yazma izni yok.';
    }

    return $errors;
}

function groo_step1_cleanup(): array
{
    $errors = [];
    $env = groo_read_env();
    $activeTemplate = $env['ACTIVE_TEMPLATE'] ?? '';

    $templatesPath = GROO_BASE . DIRECTORY_SEPARATOR . 'templates';
    
    // Aktif temayı silinmekten koru (örn: DualTesisat)
    $preserve = ['default'];
    if ($activeTemplate !== '' && is_dir($templatesPath . DIRECTORY_SEPARATOR . $activeTemplate)) {
        $preserve[] = $activeTemplate;
    }

    if (is_dir($templatesPath)) {
        groo_delete_directory($templatesPath, $preserve);
    } elseif (!@mkdir($templatesPath, 0775, true) && !is_dir($templatesPath)) {
        $errors[] = 'templates/ klasörü oluşturulamadı.';
        return $errors;
    }

    $default = $templatesPath . DIRECTORY_SEPARATOR . 'default';
    if (!is_dir($default) && !@mkdir($default, 0775, true) && !is_dir($default)) {
        $errors[] = 'templates/default klasörü oluşturulamadı.';
        return $errors;
    }

    $templateMeta = <<<'PHP'
<?php
declare(strict_types=1);
return [
    'name' => 'Default',
    'slug' => 'default',
    'version' => '1.0.0',
    'description' => 'Groo varsayılan başlangıç şablonu.',
    'author' => 'Groo',
    'requires' => [],
];
PHP;

    $templateClass = <<<'PHP'
<?php
declare(strict_types=1);
namespace Templates\Default;
use App\Core\Template\AbstractTemplate;
final class DefaultTemplate extends AbstractTemplate
{
}
PHP;

    $templateRoutes = <<<'PHP'
<?php
declare(strict_types=1);
use App\Core\Http\Router;
/** @var Router $router */
$router->get('/', static function (array $params): void {
    require __DIR__ . '/index.php';
});
PHP;

    $welcomePage = <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Groo!</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 flex items-center justify-center min-h-screen font-sans">
    <div class="text-center p-12 bg-white rounded-3xl shadow-xl shadow-slate-200 border border-slate-100 max-w-lg">
        <div class="w-20 h-20 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
        </div>
        <h1 class="text-4xl font-black tracking-tight text-slate-900 mb-4">Welcome to <span class="text-indigo-600">Groo!</span></h1>
        <p class="text-slate-500 font-medium">Kurulum tamamlandı. Kendi şablonunuzu ekleyip admin panelinden aktifleştirebilirsiniz.</p>
    </div>
</body>
</html>
HTML;

    $files = [
        'template.php' => $templateMeta,
        'DefaultTemplate.php' => $templateClass,
        'routes.php' => $templateRoutes,
        'index.php' => $welcomePage,
    ];

    foreach ($files as $name => $content) {
        if (@file_put_contents($default . DIRECTORY_SEPARATOR . $name, $content . PHP_EOL) === false) {
            $errors[] = sprintf('templates/default/%s yazılamadı.', $name);
        }
    }

    if ($activeTemplate === '') {
        groo_update_env(['ACTIVE_TEMPLATE' => 'default']);
    }

    return $errors;
}

/* ------------------------------------------------------------------ */
/* Yönlendirme Mantığı (install/index.php?step=N)                     */
/* ------------------------------------------------------------------ */

$env = groo_read_env();
$installedFlag = strtolower(trim($env['APP_INSTALLED'] ?? ''));

if ($installedFlag === 'true' || $installedFlag === '1') {
    $adminSlug = groo_sanitize_slug($env['ADMIN_PATH'] ?? 'admin');
    $adminUrl = groo_base_path() . '/' . $adminSlug . '/';

    groo_page('Zaten Kurulmuş', 0, groo_alert(
        'info',
        'Groo zaten kurulmuş görünüyor. Yeniden kurulum yapmak için <code>.env</code> dosyasındaki <code>APP_INSTALLED</code> değerini <code>false</code> yapın.'
    ) . sprintf(
        '<div class="flex justify-center gap-4 mt-8">
            <a href="%s" class="bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Siteye Git</a>
            <a href="%s" class="bg-white border-2 border-slate-200 text-slate-700 font-bold px-8 py-3 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition">Yönetim Paneli</a>
        </div>',
        groo_e(groo_base_path() . '/'),
        groo_e($adminUrl)
    ));
    exit;
}

$step = (int) ($_GET['step'] ?? 1);
if ($step < 1 || $step > 4) {
    $step = 1;
}

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($isPost && !groo_csrf_check()) {
    groo_page('Güvenlik Hatası', $step, groo_alert('error', 'Oturum doğrulanamadı (CSRF). Lütfen tekrar deneyin.'));
    exit;
}

$token = groo_csrf_token();

/* --- Adım 1: Gereksinimler & Temizlik ----------------------------- */
if ($step === 1) {
    $checkErrors = groo_step1_check();

    if ($isPost) {
        if ($checkErrors !== []) {
            groo_page('Sistem Gereksinimleri', 1, groo_alert('error', groo_e(implode(' ', $checkErrors))));
            exit;
        }

        $cleanupErrors = groo_step1_cleanup();
        if ($cleanupErrors === []) {
            header('Location: ' . groo_install_url(2), true, 303);
            exit;
        }
        
        groo_page('Temizlik Hatası', 1, groo_alert('error', groo_e(implode(' ', $cleanupErrors))));
        exit;
    }

    $reqsHtml = '';
    if ($checkErrors !== []) {
        $reqsHtml = groo_alert('error', 'Lütfen kuruluma devam etmeden önce aşağıdaki sorunları çözün:<br><br>' . implode('<br>', array_map('groo_e', $checkErrors)));
        $btn = '<button disabled class="w-full bg-slate-300 text-slate-500 font-bold px-8 py-4 rounded-xl cursor-not-allowed">Devam Edilemiyor</button>';
    } else {
        $reqsHtml = groo_alert('success', 'Harika! Sunucunuz Groo kurulumu için gerekli tüm koşulları sağlıyor.');
        $btn = '<button type="submit" class="w-full bg-indigo-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Gereksinimleri Onayla ve Devam Et</button>';
    }

    groo_page("Sistem Gereksinimleri", 1, '
        <h2 class="text-2xl font-bold text-slate-900 mb-4">Hoş Geldiniz</h2>
        <p class="text-slate-600 font-medium mb-8">Kurulum sihirbazı sistemi analiz edecek, şablon dizinini hazırlayacak ve veritabanı kurulumunu sağlayacaktır.</p>
        ' . $reqsHtml . '
        <form method="post" action="' . groo_e(groo_install_url(1)) . '">
            <input type="hidden" name="_token" value="' . groo_e($token) . '">
            ' . $btn . '
        </form>
    ');
    exit;
}

/* --- Adım 2: Veritabanı & Migrasyonlar ---------------------------- */
if ($step === 2) {
    $values = [
        'db_host' => $env['DB_HOST'] ?? 'localhost',
        'db_port' => $env['DB_PORT'] ?? '3306',
        'db_database' => $env['DB_DATABASE'] ?? 'groo',
        'db_username' => $env['DB_USERNAME'] ?? 'root',
        'db_password' => $env['DB_PASSWORD'] ?? '',
    ];

    if ($isPost) {
        $values = [
            'db_host' => trim((string) ($_POST['db_host'] ?? '')),
            'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
            'db_database' => trim((string) ($_POST['db_database'] ?? '')),
            'db_username' => trim((string) ($_POST['db_username'] ?? '')),
            'db_password' => (string) ($_POST['db_password'] ?? ''),
        ];

        try {
            if ($values['db_host'] === '' || $values['db_database'] === '' || $values['db_username'] === '') {
                throw new RuntimeException('Sunucu, veritabanı adı ve kullanıcı adı alanları zorunludur.');
            }

            $pdo = groo_db_connect($values['db_host'], $values['db_port'], $values['db_database'], $values['db_username'], $values['db_password']);

            if (!groo_update_env([
                'DB_HOST' => $values['db_host'],
                'DB_PORT' => $values['db_port'],
                'DB_DATABASE' => $values['db_database'],
                'DB_USERNAME' => $values['db_username'],
                'DB_PASSWORD' => $values['db_password'],
                'DB_CHARSET' => 'utf8mb4',
                'AUTO_MIGRATE' => 'true',
            ])) {
                throw new RuntimeException('.env dosyası güncellenemedi. Dosya yazma izinlerini kontrol edin.');
            }

            $log = groo_run_migrations($pdo);
            
            // Süper Admin rolünü oluştur
            $checkRole = $pdo->prepare('SELECT id FROM um_roles WHERE name = ?');
            $checkRole->execute(['Super Admin']);
            if (!$checkRole->fetchColumn()) {
                $pdo->prepare('INSERT INTO um_roles (name, color, permissions) VALUES (?, ?, ?)')
                    ->execute(['Super Admin', '#EF4444', '["*"]']);
            }

            $_SESSION['groo_install_migration_log'] = $log;
            header('Location: ' . groo_install_url(3), true, 303);
            exit;
        } catch (Throwable $e) {
            $content = groo_alert('error', 'Kurulum Hatası: ' . groo_e($e->getMessage()) . ' (Line: ' . $e->getLine() . ' in ' . basename($e->getFile()) . ')');
        }
    }

    if (!isset($content)) {
        $content = '';
    }

    $content .= '
        <h2 class="text-2xl font-bold text-slate-900 mb-4">Veritabanı Kurulumu</h2>
        <p class="text-slate-600 text-sm font-medium mb-8">Bağlantı bilgilerini girin. Başarılı olursa <code>.env</code> dosyasına yazılacak ve veritabanı tablolarınız (migrations) otomatik olarak inşa edilecektir.</p>
        <form method="post" action="' . groo_e(groo_install_url(2)) . '">
            <input type="hidden" name="_token" value="' . groo_e($token) . '">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                ' . groo_field('Sunucu (Host)', 'db_host', $values['db_host']) . '
                ' . groo_field('Port', 'db_port', $values['db_port']) . '
            </div>
            ' . groo_field('Veritabanı Adı', 'db_database', $values['db_database'], 'text', 'Veritabanı yoksa oluşturulmaya çalışılır.') . '
            ' . groo_field('Kullanıcı Adı', 'db_username', $values['db_username']) . '
            ' . groo_field('Şifre', 'db_password', $values['db_password'], 'password', '', false) . '
            
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-100">
                <a href="' . groo_e(groo_install_url(1)) . '" class="text-sm font-bold text-slate-500 hover:text-slate-800 transition">Geri Dön</a>
                <button type="submit" class="bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Veritabanını Kur</button>
            </div>
        </form>
    ';

    groo_page('Veritabanı', 2, $content);
    exit;
}

/* --- Adım 3: Site & Admin Yapılandırması -------------------------- */
if ($step === 3) {
    $values = [
        'site_name' => $env['APP_NAME'] ?? 'Groo',
        'admin_path' => $env['ADMIN_PATH'] ?? 'admin',
        'username' => 'admin',
        'email' => '',
    ];

    if ($isPost) {
        $siteName = (string) ($_POST['site_name'] ?? 'Groo');
        $adminPath = groo_sanitize_slug((string) ($_POST['admin_path'] ?? 'admin'));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        try {
            if ($username === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                throw new RuntimeException('Geçerli bir kullanıcı adı girin (sadece harf, rakam ve alt çizgi).');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta adresi girin.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('Şifre en az 8 karakter olmalıdır.');
            }
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('Şifreler uyuşmuyor.');
            }

            $pdo = groo_db_connect($env['DB_HOST'], $env['DB_PORT'], $env['DB_DATABASE'], $env['DB_USERNAME'], $env['DB_PASSWORD']);
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Yönetici kullanıcısını ekle veya güncelle
            $check = $pdo->prepare('SELECT id FROM um_users WHERE email = ? OR username = ? LIMIT 1');
            $check->execute([$email, $username]);
            $existing = $check->fetch();

            if ($existing) {
                $update = $pdo->prepare('UPDATE um_users SET password_hash = ?, username = ? WHERE id = ?');
                $update->execute([$hash, $username, $existing['id']]);
                $userId = $existing['id'];
            } else {
                $insert = $pdo->prepare('INSERT INTO um_users (username, email, password_hash) VALUES (?, ?, ?)');
                $insert->execute([$username, $email, $hash]);
                $userId = $pdo->lastInsertId();
            }

            // Süper Admin rolüne bağla
            $roleQuery = $pdo->prepare('SELECT id FROM um_roles WHERE name = ?');
            $roleQuery->execute(['Super Admin']);
            $roleId = $roleQuery->fetchColumn();

            if ($roleId) {
                $pdo->prepare('INSERT IGNORE INTO um_user_roles (user_id, role_id) VALUES (?, ?)')
                    ->execute([$userId, $roleId]);
            }

            // SEO Ayarlarını kaydet
            $pdo->prepare('INSERT INTO seo_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?')
                ->execute(['site_title', $siteName, $siteName]);

            if (!groo_update_env(['APP_NAME' => $siteName, 'ADMIN_PATH' => $adminPath])) {
                throw new RuntimeException('.env dosyası güncellenemedi.');
            }

            $_SESSION['groo_install_admin_email'] = $email;
            header('Location: ' . groo_install_url(4), true, 303);
            exit;
        } catch (Throwable $e) {
            $content = groo_alert('error', 'Hata: ' . groo_e($e->getMessage()));
            $values = array_merge($values, $_POST);
        }
    }

    if (!isset($content)) {
        $content = '';
    }

    $migrationLog = $_SESSION['groo_install_migration_log'] ?? [];
    if ($migrationLog !== []) {
        unset($_SESSION['groo_install_migration_log']);
        $content .= groo_alert('success', 'Migrasyonlar başarıyla çalıştırıldı (' . count($migrationLog) . ' tablo oluşturuldu).');
    }

    $content .= '
        <h2 class="text-2xl font-bold text-slate-900 mb-4">Site & Yönetici Ayarları</h2>
        <p class="text-slate-600 text-sm font-medium mb-8">Sitenizin temel bilgilerini ve yetkili yönetici hesap bilgilerinizi oluşturun.</p>
        <form method="post" action="' . groo_e(groo_install_url(3)) . '">
            <input type="hidden" name="_token" value="' . groo_e($token) . '">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                ' . groo_field('Site Adı', 'site_name', $values['site_name']) . '
                ' . groo_field('Yönetim Paneli URL (ADMIN_PATH)', 'admin_path', $values['admin_path'], 'text', 'Örn: yonetim → site.com/yonetim') . '
            </div>
            
            <div class="h-px w-full bg-slate-100 my-4"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                ' . groo_field('Kullanıcı Adı', 'username', $values['username']) . '
                ' . groo_field('E-posta', 'email', $values['email'], 'email') . '
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                ' . groo_field('Şifre', 'password', '', 'password', 'En az 8 karakter') . '
                ' . groo_field('Şifre (Tekrar)', 'password_confirm', '', 'password') . '
            </div>

            <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-100">
                <a href="' . groo_e(groo_install_url(2)) . '" class="text-sm font-bold text-slate-500 hover:text-slate-800 transition">Geri Dön</a>
                <button type="submit" class="bg-indigo-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Ayarları Kaydet</button>
            </div>
        </form>
    ';

    groo_page('Site & Yönetici', 3, $content);
    exit;
}

/* --- Adım 4: Tamamlanma & Self-Destruct --------------------------- */
if ($step === 4) {
    $email = $_SESSION['groo_install_admin_email'] ?? 'Yönetici';
    
    // APP_INSTALLED = true
    groo_update_env(['APP_INSTALLED' => 'true']);
    
    $env = groo_read_env();
    $appEnv = strtolower(trim($env['APP_ENV'] ?? 'production'));
    $isDevelopment = in_array($appEnv, ['development', 'local'], true);

    $removed = $isDevelopment ? null : groo_delete_directory(GROO_INSTALL_DIR);

    $adminUrl = groo_base_path() . '/' . groo_sanitize_slug($env['ADMIN_PATH'] ?? 'admin') . '/';

    $note = '';
    if ($isDevelopment) {
        $note = groo_alert('info', 'Geliştirme ortamı algılandı (<code>APP_ENV=' . groo_e($appEnv) . '</code>). <code>install/</code> klasörü silinmedi; testleriniz bittikten sonra manuel olarak silebilirsiniz.');
    } elseif ($removed) {
        $note = groo_alert('success', 'Güvenlik için <code>install/</code> klasörü ve tüm migrasyonlar otomatik olarak silindi (Self-Destruct).');
    } else {
        $note = groo_alert('error', '<code>install/</code> klasörü otomatik olarak silinemedi. Lütfen klasörü sunucudan manuel olarak silin.');
    }

    groo_page('Tamamlandı', 4, '
        <div class="text-center">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-emerald-100 flex items-center justify-center">
                <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-3xl font-black text-slate-900 mb-4">Her Şey Hazır!</h2>
            <p class="text-slate-600 font-medium mb-2">Groo başarıyla kuruldu. Hesabınız <strong>' . groo_e($email) . '</strong> olarak ayarlandı.</p>
            <p class="text-slate-600 font-medium mb-8">Yönetim panelinize <a href="' . groo_e($adminUrl) . '" class="text-indigo-600 font-bold hover:underline">' . groo_e($adminUrl) . '</a> adresinden ulaşabilirsiniz. 5 saniye içinde yönlendirileceksiniz.</p>
            ' . $note . '
            <a href="' . groo_e($adminUrl) . '" class="inline-block mt-4 bg-indigo-600 text-white font-bold px-10 py-4 rounded-xl hover:bg-indigo-700 transition shadow-xl shadow-indigo-200">Yönetim Paneline Git</a>
        </div>
        <script>setTimeout(function () { window.location.href = ' . json_encode($adminUrl) . '; }, 5000);</script>
    ');
    exit;
}
