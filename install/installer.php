<?php

/*
| Biofy.TR web installer (no SSH / Composer / Node needed).
| Reached only through public/index.php while storage/app/private/installed.lock is missing.
| Steps: requirements -> install key -> database -> site & superadmin -> install -> done.
*/

if (! defined('BIOFY_BASE') || ! defined('BIOFY_PUBLIC')) {
    http_response_code(404);
    exit;
}

require __DIR__.'/Requirements.php';
require __DIR__.'/Database.php';
require __DIR__.'/EnvWriter.php';

use BiofyInstaller\Database;
use BiofyInstaller\EnvWriter;
use BiofyInstaller\Requirements;

const BI_LOCK = BIOFY_BASE.'/storage/app/private/installed.lock';
const BI_KEY_FILE = BIOFY_BASE.'/storage/app/private/install.key';
const BI_USERNAME_PATTERN = '/^(?=.{3,30}$)[a-z0-9](?:[a-z0-9]|[_-](?=[a-z0-9]))*$/';

@set_time_limit(300);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

session_name('biofy_install');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => Requirements::isHttps(), 'httponly' => true, 'samesite' => 'Strict']);
session_start();

$lang = in_array($_GET['lang'] ?? null, ['tr', 'en'], true) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'tr');
$_SESSION['lang'] = $lang;
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

$T = require __DIR__.'/lang.php';
$t = fn (string $key, array $vars = []) => strtr($T[$lang][$key] ?? $T['tr'][$key] ?? $key, array_combine(array_map(fn ($k) => ':'.$k, array_keys($vars)), array_values($vars)) ?: []);
$e = fn (?string $value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

if (is_file(BI_LOCK)) {
    bi_render($t('already_installed'), '<p>'.$e($t('already_installed_text')).'</p>', $lang, $t);
    exit;
}

$requirements = new Requirements(BIOFY_BASE, BIOFY_PUBLIC);
$step = $_SESSION['step'] ?? 'requirements';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! hash_equals($_SESSION['csrf'], (string) ($_POST['_token'] ?? ''))) {
        $errors[] = $t('csrf');
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'requirements' && $requirements->passes()) {
            $step = 'key';
        } elseif ($action === 'key') {
            $attempts = ($_SESSION['key_attempts'] ?? 0) + 1;
            $_SESSION['key_attempts'] = $attempts;
            $expected = is_file(BI_KEY_FILE) ? trim((string) file_get_contents(BI_KEY_FILE)) : '';

            if ($attempts > 10) {
                $errors[] = $t('key_locked');
            } elseif ($expected !== '' && hash_equals($expected, trim((string) ($_POST['install_key'] ?? '')))) {
                $_SESSION['key_ok'] = true;
                $step = 'database';
            } else {
                $errors[] = $t('key_invalid');
            }
        } elseif ($action === 'database' && ! empty($_SESSION['key_ok'])) {
            $db = [
                'host' => trim((string) ($_POST['host'] ?? 'localhost')),
                'port' => (int) ($_POST['port'] ?? 3306) ?: 3306,
                'database' => trim((string) ($_POST['database'] ?? '')),
                'username' => trim((string) ($_POST['username'] ?? '')),
                'password' => (string) ($_POST['password'] ?? ''),
                'prefix' => trim((string) ($_POST['prefix'] ?? '')),
            ];
            $result = $db['database'] === '' || $db['username'] === '' ? ['ok' => false, 'error' => 'connection', 'server' => $t('db_required')] : Database::test($db);

            if ($result['ok']) {
                $_SESSION['db'] = $db;
                $step = 'site';
            } else {
                $errors[] = $t('db_error_'.$result['error']).($result['server'] ? ' ('.$result['server'].')' : '');
                $_SESSION['db_form'] = array_diff_key($db, ['password' => true]);
            }
        } elseif ($action === 'site' && ! empty($_SESSION['db'])) {
            $site = [
                'url' => rtrim(trim((string) ($_POST['url'] ?? '')), '/'),
                'name' => trim((string) ($_POST['name'] ?? '')),
                'locale' => in_array($_POST['locale'] ?? 'tr', ['tr', 'en'], true) ? $_POST['locale'] : 'tr',
                'timezone' => (string) ($_POST['timezone'] ?? 'Europe/Istanbul'),
                'admin_name' => trim((string) ($_POST['admin_name'] ?? '')),
                'admin_username' => strtolower(trim((string) ($_POST['admin_username'] ?? ''))),
                'admin_email' => strtolower(trim((string) ($_POST['admin_email'] ?? ''))),
                'admin_password' => (string) ($_POST['admin_password'] ?? ''),
            ];

            // The application must run at the root of a (sub)domain, not in a sub-folder.
            $urlPath = (string) parse_url($site['url'], PHP_URL_PATH);
            if (! filter_var($site['url'], FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $site['url']) || ($urlPath !== '' && $urlPath !== '/')) {
                $errors[] = $t('url_invalid');
            }
            if ($site['name'] === '' || mb_strlen($site['name']) > 60) {
                $errors[] = $t('name_invalid');
            }
            if (! in_array($site['timezone'], timezone_identifiers_list(), true)) {
                $errors[] = $t('timezone_invalid');
            }
            if ($site['admin_name'] === '') {
                $errors[] = $t('admin_name_invalid');
            }
            if (! preg_match(BI_USERNAME_PATTERN, $site['admin_username'])) {
                $errors[] = $t('username_invalid');
            }
            if (! filter_var($site['admin_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $t('email_invalid');
            }
            if (mb_strlen($site['admin_password']) < 10 || ! preg_match('/\pL/u', $site['admin_password']) || ! preg_match('/\d/', $site['admin_password'])) {
                $errors[] = $t('password_invalid');
            } elseif (! hash_equals($site['admin_password'], (string) ($_POST['admin_password_confirmation'] ?? ''))) {
                $errors[] = $t('password_mismatch');
            }

            if ($errors === []) {
                $_SESSION['site'] = $site;
                $step = 'confirm';
            } else {
                $_SESSION['site_form'] = array_diff_key($site, ['admin_password' => true]);
            }
        } elseif ($action === 'install' && ! empty($_SESSION['db']) && ! empty($_SESSION['site'])) {
            try {
                bi_install($_SESSION['db'], $_SESSION['site']);
                $cronToken = $GLOBALS['bi_cron_token'] ?? '';
                $siteUrl = $_SESSION['site']['url'];
                $_SESSION = ['lang' => $lang, 'csrf' => $_SESSION['csrf']];
                $done = '<div class="ok">'.$e($t('done_text')).'</div>'
                    .'<h3>'.$e($t('next_steps')).'</h3><ol>'
                    .'<li>'.$e($t('next_login')).' <a href="'.$e($siteUrl).'/login">'.$e($siteUrl).'/login</a></li>'
                    .'<li>'.$e($t('next_mail')).'</li>'
                    .'<li>'.$e($t('next_cron')).'<br><code>* * * * * php '.$e(BIOFY_BASE).'/artisan schedule:run &gt;/dev/null 2&gt;&amp;1</code><br>'.$e($t('next_cron_url')).'<br><code>'.$e($siteUrl.'/cron/'.$cronToken).'</code></li>'
                    .'<li>'.$e($t('next_probe')).'</li>'
                    .'<li>'.$e($t('next_backup')).'</li></ol>';
                bi_render($t('done'), $done, $lang, $t);
                exit;
            } catch (Throwable $ex) {
                bi_log($ex);
                $errors[] = $t('install_failed').' '.mb_substr(preg_replace('/(password|pwd)=\S+/i', '$1=***', $ex->getMessage()), 0, 400);
                $step = 'confirm';
            }
        } elseif ($action === 'back') {
            $step = match ($step) { 'confirm' => 'site', 'site' => 'database', default => 'requirements' };
        }
    }

    // Post/Redirect/Get: errors travel through the session to the next page view.
    $_SESSION['step'] = $step;
    $_SESSION['flash_errors'] = $errors;
    header('Location: '.strtok((string) $_SERVER['REQUEST_URI'], '?').'?'.http_build_query(['lang' => $lang]), true, 303);
    exit;
}

$errors = $_SESSION['flash_errors'] ?? [];
unset($_SESSION['flash_errors']);
$csrf = $e($_SESSION['csrf']);
$form = fn (string $action, string $inner, string $button) => '<form method="post" autocomplete="off"><input type="hidden" name="_token" value="'.$csrf.'"><input type="hidden" name="action" value="'.$action.'">'.$inner.'<div class="actions">'.($action !== 'requirements' ? '<button class="ghost" name="action" value="back" formnovalidate>'.$e($t('back')).'</button>' : '<span></span>').'<button>'.$e($button).'</button></div></form>';
$field = fn (string $name, string $label, string $value = '', string $type = 'text', string $extra = '') => '<label>'.$e($label).'<input type="'.$type.'" name="'.$name.'" value="'.$e($value).'" '.$extra.'></label>';

$body = $errors ? '<div class="err"><ul><li>'.implode('</li><li>', array_map($e, $errors)).'</li></ul></div>' : '';

switch ($step) {
    case 'key':
        if (! is_file(BI_KEY_FILE)) {
            @file_put_contents(BI_KEY_FILE, bin2hex(random_bytes(12))."\n", LOCK_EX);
            @chmod(BI_KEY_FILE, 0640);
        }
        $body .= '<p>'.$e($t('key_text')).'</p><p><code>storage/app/private/install.key</code></p>'
            .$form('key', $field('install_key', $t('install_key'), '', 'text', 'required autofocus spellcheck="false"'), $t('continue'));
        break;

    case 'database':
        $d = $_SESSION['db_form'] ?? ['host' => 'localhost', 'port' => 3306, 'database' => '', 'username' => '', 'prefix' => ''];
        $body .= '<p>'.$e($t('db_text')).'</p>'.$form('database',
            $field('host', $t('db_host'), (string) $d['host'], 'text', 'required')
            .$field('port', $t('db_port'), (string) $d['port'], 'number', 'required')
            .$field('database', $t('db_name'), (string) $d['database'], 'text', 'required')
            .$field('username', $t('db_user'), (string) $d['username'], 'text', 'required')
            .$field('password', $t('db_pass'), '', 'password')
            .$field('prefix', $t('db_prefix'), (string) $d['prefix'], 'text', 'pattern="[A-Za-z0-9_]{0,20}"'),
            $t('test_continue'));
        break;

    case 'site':
        $scheme = Requirements::isHttps() ? 'https' : 'http';
        $detected = $scheme.'://'.preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $s = ($_SESSION['site_form'] ?? []) + ['url' => $detected, 'name' => 'Biofy.TR', 'locale' => $lang, 'timezone' => 'Europe/Istanbul', 'admin_name' => '', 'admin_username' => '', 'admin_email' => ''];
        $zones = '';
        foreach (timezone_identifiers_list() as $zone) {
            $zones .= '<option'.($zone === $s['timezone'] ? ' selected' : '').'>'.$e($zone).'</option>';
        }
        $body .= ($scheme === 'http' ? '<div class="warn">'.$e($t('https_warning')).'</div>' : '')
            .$form('site',
                '<h3>'.$e($t('site')).'</h3>'
                .$field('url', $t('site_url'), $s['url'], 'url', 'required').'<p class="help">'.$e($t('site_url_help')).'</p>'
                .$field('name', $t('site_name'), $s['name'], 'text', 'required maxlength="60"')
                .'<label>'.$e($t('locale')).'<select name="locale"><option value="tr"'.($s['locale'] === 'tr' ? ' selected' : '').'>Türkçe</option><option value="en"'.($s['locale'] === 'en' ? ' selected' : '').'>English</option></select></label>'
                .'<label>'.$e($t('timezone')).'<select name="timezone">'.$zones.'</select></label>'
                .'<h3>'.$e($t('admin')).'</h3><p class="help">'.$e($t('admin_help')).'</p>'
                .$field('admin_name', $t('admin_name'), $s['admin_name'], 'text', 'required')
                .$field('admin_username', $t('admin_username'), $s['admin_username'], 'text', 'required pattern="[a-z0-9_-]{3,30}"')
                .$field('admin_email', $t('admin_email'), $s['admin_email'], 'email', 'required')
                .$field('admin_password', $t('admin_password'), '', 'password', 'required minlength="10" autocomplete="new-password"')
                .$field('admin_password_confirmation', $t('admin_password_confirm'), '', 'password', 'required autocomplete="new-password"'),
                $t('continue'));
        break;

    case 'confirm':
        $s = $_SESSION['site'];
        $db = $_SESSION['db'];
        $body .= '<p>'.$e($t('confirm_text')).'</p><table>'
            .'<tr><th>'.$e($t('site_url')).'</th><td>'.$e($s['url']).'</td></tr>'
            .'<tr><th>'.$e($t('site_name')).'</th><td>'.$e($s['name']).'</td></tr>'
            .'<tr><th>'.$e($t('db_name')).'</th><td>'.$e($db['username'].'@'.$db['host'].':'.$db['port'].'/'.$db['database']).'</td></tr>'
            .'<tr><th>'.$e($t('admin_email')).'</th><td>'.$e($s['admin_email']).'</td></tr></table>'
            .$form('install', '', $t('install'));
        break;

    default:
        $rows = '';
        foreach ($requirements->all() as $check) {
            $state = $check['ok'] ? '<span class="good">✔</span>' : ($check['required'] ? '<span class="bad">✖</span>' : '<span class="soft">!</span>');
            $rows .= '<tr><td>'.$state.'</td><td>'.$e($t('req_'.explode(':', $check['key'])[0], ['x' => $check['detail'] ?: (explode(':', $check['key'])[1] ?? '')])).'</td><td>'.($check['required'] ? $e($t('required')) : $e($t('recommended'))).'</td></tr>';
        }
        $passes = $requirements->passes();
        $body .= '<p>'.$e($t('intro')).'</p><table class="req">'.$rows.'</table>'
            .($passes ? $form('requirements', '', $t('continue')) : '<div class="err">'.$e($t('req_failed')).'</div><p><a href="?lang='.$lang.'">'.$e($t('recheck')).'</a></p>');
}

bi_render($t('step_'.$step), $body, $lang, $t);

/** Performs the installation. Throws on failure (nothing is locked until the very end). */
function bi_install(array $db, array $site): void
{
    if (! is_file(BIOFY_BASE.'/.env') || ! str_contains((string) file_get_contents(BIOFY_BASE.'/.env'), 'APP_KEY=base64:')) {
        $key = EnvWriter::generateKey();
    } else {
        preg_match('/^APP_KEY=(\S+)/m', (string) file_get_contents(BIOFY_BASE.'/.env'), $m);
        $key = $m[1] ?? EnvWriter::generateKey();
    }

    $release = is_file(BIOFY_BASE.'/release-public-key.txt') ? trim((string) file_get_contents(BIOFY_BASE.'/release-public-key.txt')) : '';
    $env = EnvWriter::build([
        'APP_NAME' => $site['name'], 'APP_KEY' => $key, 'APP_URL' => $site['url'], 'APP_LOCALE' => $site['locale'],
        'DB_HOST' => $db['host'], 'DB_PORT' => $db['port'], 'DB_DATABASE' => $db['database'], 'DB_USERNAME' => $db['username'],
        'DB_PASSWORD' => $db['password'], 'DB_PREFIX' => $db['prefix'], 'BIOFY_RELEASE_PUBLIC_KEY' => $release,
    ]);

    if (! EnvWriter::write(BIOFY_BASE.'/.env', $env)) {
        throw new RuntimeException('The .env file could not be written.');
    }

    // Remember where public/ lives when it is not next to the application (public_html setups).
    $defaultPublic = realpath(BIOFY_BASE.'/public');
    if ($defaultPublic !== realpath(BIOFY_PUBLIC)) {
        file_put_contents(BIOFY_BASE.'/bootstrap/public-path.php', "<?php\n\nreturn ".var_export(realpath(BIOFY_PUBLIC), true).";\n");
    }

    require BIOFY_BASE.'/vendor/autoload.php';
    $app = require BIOFY_BASE.'/bootstrap/app.php';
    $app->usePublicPath(BIOFY_PUBLIC);
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Checked before migrating (the reserved_usernames table does not exist yet and is empty on a fresh install).
    if (in_array($site['admin_username'], config('biofy.reserved_slugs'), true)) {
        throw new RuntimeException('The username "'.$site['admin_username'].'" is reserved; choose another one.');
    }

    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    Illuminate\Support\Facades\Cache::flush();
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

    $settings = app(App\Services\Settings::class);
    $settings->setMany([
        'site.name' => $site['name'],
        'site.default_locale' => $site['locale'],
        'site.timezone' => $site['timezone'],
        'site.support_email' => $site['admin_email'],
        'mail.from_name' => $site['name'],
        'system.installed_version' => config('biofy.version'),
        'system.installed_at' => now()->toIso8601String(),
    ]);

    $user = new App\Models\User([
        'name' => $site['admin_name'],
        'username' => $site['admin_username'],
        'email' => $site['admin_email'],
        'password' => $site['admin_password'],
        'locale' => $site['locale'],
    ]);
    $user->email_verified_at = now();
    $user->password_changed_at = now();
    $user->save();
    $user->roles()->attach(App\Models\Role::query()->where('key', App\Models\Role::SUPERADMIN)->value('id'), ['created_at' => now()]);

    if ($package = app(App\Services\Entitlements::class)->defaultPackage()) {
        App\Models\UserPackageAssignment::query()->create(['user_id' => $user->id, 'package_id' => $package->id, 'starts_at' => now(), 'source' => 'system']);
    }

    app(App\Services\AuditLogger::class)->log('system.installed', $user, ['version' => config('biofy.version')], $user);
    $GLOBALS['bi_cron_token'] = (string) $settings->get('cron.token');

    // Lock the installer: from now on index.php boots the application instead.
    file_put_contents(BI_LOCK, json_encode(['installed_at' => gmdate('c'), 'version' => config('biofy.version')]), LOCK_EX);
    @unlink(BI_KEY_FILE);
}

function bi_log(Throwable $e): void
{
    $line = '['.gmdate('c').'] '.get_class($e).': '.preg_replace('/(password|pwd)=\S+/i', '$1=***', $e->getMessage()).' @ '.$e->getFile().':'.$e->getLine()."\n";
    @file_put_contents(BIOFY_BASE.'/storage/logs/installer.log', $line, FILE_APPEND | LOCK_EX);
}

function bi_render(string $title, string $body, string $lang, Closure $t): void
{
    $other = $lang === 'tr' ? 'en' : 'tr';
    echo '<!DOCTYPE html><html lang="'.$lang.'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
        .'<title>'.htmlspecialchars($title).' · Biofy.TR</title><style>'
        .':root{--y:#FFCF00;--ink:#111}*{box-sizing:border-box}body{margin:0;background:#f4f4f5;color:#18181b;font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        .'.wrap{max-width:640px;margin:40px auto;padding:0 16px}.card{background:#fff;border:1px solid #e4e4e7;border-radius:16px;padding:28px}'
        .'header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}.logo{display:flex;gap:8px;align-items:center;font-weight:800}.logo span{display:grid;place-items:center;width:30px;height:30px;border-radius:9px;background:var(--y)}'
        .'h1{font-size:22px;margin:0 0 14px}h3{margin:22px 0 8px;font-size:15px}label{display:block;margin:12px 0 0;font-weight:600;font-size:14px}input,select{display:block;width:100%;margin-top:6px;padding:10px 12px;border:1px solid #d4d4d8;border-radius:10px;font:inherit}'
        .'input:focus,select:focus{outline:2px solid var(--y);border-color:var(--ink)}button{background:var(--y);color:var(--ink);border:0;border-radius:10px;padding:11px 18px;font-weight:700;font-size:15px;cursor:pointer}button.ghost{background:transparent;color:#52525b}'
        .'.actions{display:flex;justify-content:space-between;margin-top:22px}table{width:100%;border-collapse:collapse;margin:12px 0}td,th{padding:7px 6px;border-bottom:1px solid #f4f4f5;text-align:left;font-size:14px}.req td:last-child{color:#71717a;font-size:12px}'
        .'.good{color:#059669}.bad{color:#dc2626;font-weight:700}.soft{color:#d97706;font-weight:700}.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:10px 14px;margin:0 0 14px}'
        .'.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;padding:10px 14px;margin:0 0 14px}.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:12px 14px}'
        .'code{background:#f4f4f5;padding:2px 6px;border-radius:6px;font-size:13px;word-break:break-all}.help{color:#71717a;font-size:12px;margin:4px 0 0}a{color:var(--ink)}ul{margin:0;padding-left:18px}'
        .'</style></head><body><div class="wrap"><header><div class="logo"><span>b</span>Biofy.TR '.htmlspecialchars($t('installer')).'</div><a href="?lang='.$other.'">'.strtoupper($other).'</a></header>'
        .'<main class="card"><h1>'.htmlspecialchars($title).'</h1>'.$body.'</main></div></body></html>';
}
