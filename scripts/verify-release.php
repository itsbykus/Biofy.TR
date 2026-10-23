<?php

/*
| Verifies an FTP release package before it is published or installed.
|
|   php scripts/verify-release.php dist/biofy-tr-<version>.zip
|       [--public-key=<base64>]         the manifest must be signed by this key (the one pinned in existing installs)
|       [--forbid-content-of=<file>]    the content of this file (e.g. the signing key) must not appear in the package
|
| Checks: zip integrity, folder/version naming, required installer and runtime files, vendor/ with
| every production package and no dev package, public/build/ with every file of the Vite manifest,
| no .env / secrets / local state / development files, the signed release manifest against the
| actual zip content, and the <zip>.sha256 file against the zip's real SHA-256.
| Exits with 1 when any check fails. Also run by scripts/build-release.php and the CI workflows.
*/

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$args = array_slice($argv, 1);
$zipPath = null;
$expectedKey = null;
$forbidden = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--public-key=')) {
        $expectedKey = trim(substr($arg, 13));
    } elseif (str_starts_with($arg, '--forbid-content-of=')) {
        $file = substr($arg, 20);
        $content = is_file($file) ? trim((string) file_get_contents($file)) : '';
        if (strlen($content) < 16) {
            fwrite(STDERR, "--forbid-content-of: {$file} is missing or too short to search for\n");
            exit(2);
        }
        $forbidden[] = $content;
    } else {
        $zipPath = $arg;
    }
}

if ($zipPath === null || ! is_file($zipPath)) {
    fwrite(STDERR, "Usage: php scripts/verify-release.php <package.zip> [--public-key=<base64>] [--forbid-content-of=<file>]\n");
    exit(2);
}

$failures = 0;

function ok(string $message): void
{
    fwrite(STDOUT, "  ok    {$message}\n");
}

function bad(string $message): void
{
    global $failures;
    $failures++;
    fwrite(STDOUT, "  FAIL  {$message}\n");
}

function check(bool $condition, string $message, string $failure = ''): bool
{
    $condition ? ok($message) : bad($message.($failure !== '' ? ' — '.$failure : ''));

    return $condition;
}

function finish(): never
{
    global $failures, $zipPath;
    fwrite(STDOUT, $failures === 0
        ? "\nPackage verified: ".basename($zipPath)."\n"
        : "\n{$failures} check(s) failed: ".basename($zipPath)." must not be published or installed.\n");
    exit($failures === 0 ? 0 : 1);
}

fwrite(STDOUT, 'Verifying '.basename($zipPath)."\n");

$zip = new ZipArchive;
if (($code = $zip->open($zipPath, ZipArchive::CHECKCONS)) !== true) {
    bad("zip archive can be opened (error code {$code})");
    finish();
}
ok('zip archive is consistent ('.$zip->numFiles.' entries)');

// --- Layout: one top-level folder named after the package ------------------------------
$entries = [];
$roots = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    $roots[explode('/', $name)[0]] = true;
    if (! str_ends_with($name, '/')) {
        $entries[$name] = $i;
    }
}

$folder = basename($zipPath, '.zip');
if (! check(array_keys($roots) === [$folder], "single top-level folder {$folder}/", 'found: '.implode(', ', array_slice(array_keys($roots), 0, 5)))) {
    finish();
}

// Paths below are relative to the top-level folder.
$files = [];
foreach ($entries as $name => $index) {
    $files[substr($name, strlen($folder) + 1)] = $index;
}
$has = fn (string $path) => isset($files[$path]);
$read = fn (string $path) => $has($path) ? (string) $zip->getFromIndex($files[$path]) : '';

preg_match("/'version' => '([^']+)'/", $read('config/biofy.php'), $m);
$version = $m[1] ?? '';
check($version !== '' && $folder === 'biofy-tr-'.$version, "package name matches config/biofy.php version ({$version})", "expected biofy-tr-{$version}");

// --- GitHub's "Source code" archive is the most common wrong download ---------------------
if (! $has('vendor/autoload.php') && $has('package.json') && $has('vite.config.js')) {
    bad('this is a source code archive (no vendor/, contains package.json and vite.config.js), not the installable release package; build one with php scripts/build-release.php or download biofy-tr-<version>.zip from the release assets');
}

// --- Required files ---------------------------------------------------------------------
$required = [
    // web entry points and access rules
    '.htaccess', 'public/index.php', 'public/.htaccess', 'public/media/.htaccess', 'public/robots.txt', 'public/favicon.ico',
    // web installer
    'install/installer.php', 'install/Requirements.php', 'install/Database.php', 'install/EnvWriter.php', 'install/lang.php', 'install/.htaccess',
    // application
    'artisan', 'bootstrap/app.php', 'bootstrap/providers.php', 'config/app.php', 'config/biofy.php', 'config/database.php',
    'routes/web.php', 'routes/api.php', 'routes/console.php', 'database/seeders/DatabaseSeeder.php', 'app/helpers.php',
    'composer.json', 'composer.lock', '.env.example',
    // production dependencies
    'vendor/autoload.php', 'vendor/composer/autoload_real.php', 'vendor/composer/autoload_static.php', 'vendor/composer/installed.json',
    'vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    // compiled front-end
    'public/build/manifest.json',
    // runtime folders (placeholders) and their access guards
    'storage/.htaccess', 'storage/app/private/.gitignore', 'storage/app/public/.gitignore', 'storage/framework/cache/data/.gitignore',
    'storage/framework/sessions/.gitignore', 'storage/framework/views/.gitignore', 'storage/logs/.gitignore', 'bootstrap/cache/.gitignore',
    // web access guards of the application folders (layout C: whole package inside public_html)
    'app/.htaccess', 'bootstrap/.htaccess', 'config/.htaccess', 'database/.htaccess', 'lang/.htaccess', 'resources/.htaccess',
    'routes/.htaccess', 'vendor/.htaccess', 'docs/.htaccess',
    // documentation and release integrity
    'docs/INSTALLATION.md', 'docs/SHARED_HOSTING.md', 'docs/UPDATE.md', 'README.md', 'LICENSE', 'CHANGELOG.md',
    'release-manifest.json', 'release-manifest.sig', 'release-public-key.txt',
];
$missing = array_values(array_filter($required, fn ($path) => ! $has($path)));
check($missing === [], count($required).' required installer, runtime and documentation files present', 'missing: '.implode(', ', $missing));

$migrations = array_filter(array_keys($files), fn ($path) => preg_match('#^database/migrations/[^/]+\.php$#', $path));
check(count($migrations) > 0, count($migrations).' database migrations present');
check(count(array_filter(array_keys($files), fn ($path) => str_starts_with($path, 'resources/views/'))) > 0, 'Blade views present (resources/views/)');

// --- vendor/: every production package, no development package ---------------------------
$lock = json_decode($read('composer.lock'), true);
$installed = json_decode($read('vendor/composer/installed.json'), true);
if (check(is_array($lock) && is_array($installed), 'composer.lock and vendor/composer/installed.json readable')) {
    $installedNames = array_column($installed['packages'] ?? $installed, 'name');
    $prod = array_column($lock['packages'] ?? [], 'name');
    $dev = array_column($lock['packages-dev'] ?? [], 'name');

    $notInstalled = array_values(array_diff($prod, $installedNames));
    check($notInstalled === [], count($prod).' production packages from composer.lock installed', 'missing: '.implode(', ', $notInstalled));
    check(($installed['dev'] ?? true) === false, 'vendor/ installed with --no-dev');

    $devShipped = array_values(array_filter($dev, function ($name) use ($files) {
        foreach (array_keys($files) as $path) {
            if (str_starts_with($path, 'vendor/'.$name.'/')) {
                return true;
            }
        }

        return false;
    }));
    check($devShipped === [], 'no development package in vendor/ ('.count($dev).' checked)', 'found: '.implode(', ', $devShipped));
}

// --- public/build/: every file referenced by the Vite manifest ---------------------------
$vite = json_decode($read('public/build/manifest.json'), true);
if (check(is_array($vite) && $vite !== [], 'public/build/manifest.json is valid JSON with entries')) {
    $referenced = [];
    foreach ($vite as $entry) {
        foreach (array_merge([$entry['file'] ?? ''], $entry['css'] ?? [], $entry['assets'] ?? []) as $asset) {
            $referenced[$asset] = true;
        }
    }
    $absent = array_values(array_filter(array_keys($referenced), fn ($asset) => $asset === '' || ! $has('public/build/'.$asset)));
    check($absent === [], count($referenced).' files referenced by the Vite manifest present in public/build/', 'missing: '.implode(', ', array_slice($absent, 0, 10)));
}

// --- Nothing secret, local or development-only ---------------------------------------------
$rules = [
    '#(^|/)\.env(\.(?!example$)[^/]+)?$#' => '.env file',
    '#(^|/)auth\.json$#' => 'Composer credentials (auth.json)',
    '#(^|/)(\.git|\.github|\.idea|\.vscode|\.claude|\.cursor|\.codex|node_modules)/#' => 'version control / editor / node_modules',
    '#^(tests|dist|scripts|docker)/#' => 'development folder',
    '#^(phpunit\.xml|\.phpunit\.result\.cache|package(-lock)?\.json|vite\.config\.js|Dockerfile|docker-compose\.yml)$#' => 'development file',
    '#^(?!vendor/).*\.(key|pem|p12|pfx|crt)$#i' => 'key or certificate file',
    '#\.(sqlite3?|sql|log|biofy-backup)$#i' => 'database, log or backup file',
    '#^storage/(?!(.*/)?\.(gitignore|htaccess)$).+#' => 'runtime data in storage/',
    '#^bootstrap/cache/(?!\.gitignore$).+#' => 'framework cache',
    '#^bootstrap/public-path\.php$#' => 'installation-specific public path',
    '#^public/(hot|storage)(/|$)#' => 'Vite dev server marker / storage link',
    '#^public/media/(?!\.htaccess$).+#' => 'user upload',
];
$hits = [];
foreach (array_keys($files) as $path) {
    foreach ($rules as $pattern => $label) {
        if (preg_match($pattern, $path)) {
            $hits[] = "{$path} ({$label})";
        }
    }
}
check($hits === [], 'no .env, secrets, local state or development files', implode(', ', array_slice($hits, 0, 10)));

$patterns = [
    '/^\s*APP_KEY\s*=\s*["\']?base64:[A-Za-z0-9+\/]{40,}/m' => 'application key',
    '/-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----\r?\n[A-Za-z0-9+\/=]{40,}/' => 'private key',
    '/\bgh[pousr]_[A-Za-z0-9]{36}\b|\bgithub_pat_[A-Za-z0-9_]{60,}/' => 'GitHub token',
    '/\bAKIA[0-9A-Z]{16}\b/' => 'AWS access key',
    '/\bsk-ant-[A-Za-z0-9_-]{20,}/' => 'Anthropic API key',
    '/\bGOCSPX-[A-Za-z0-9_-]{20,}/' => 'Google OAuth client secret',
    '/\bxox[abprs]-[A-Za-z0-9-]{10,}/' => 'Slack token',
];
// Reviewed, publicly known examples in upstream documentation. Anything new fails the build until reviewed.
$knownExamples = [
    'vendor/firebase/php-jwt/README.md' => 'private key', // sample RSA key of the RS256 usage example
];
$leaks = [];
$scanned = 0;
foreach ($files as $path => $index) {
    $content = (string) $zip->getFromIndex($index);
    foreach ($forbidden as $secret) {
        if (str_contains($content, $secret)) {
            $leaks[] = "{$path} (signing key)";
        }
    }
    if (str_contains(substr($content, 0, 8000), "\0")) {
        continue; // binary
    }
    $scanned++;
    foreach ($patterns as $pattern => $label) {
        if (($knownExamples[$path] ?? null) !== $label && preg_match($pattern, $content)) {
            $leaks[] = "{$path} ({$label})";
        }
    }
}
check($leaks === [], "no secrets in file contents ({$scanned} text files scanned".($forbidden !== [] ? ', signing key searched in all files' : '').')', implode(', ', array_slice($leaks, 0, 10)));

$example = $read('.env.example');
check(! preg_match('/^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|GOOGLE_CLIENT_SECRET)=\S+/m', $example), '.env.example holds no values for secrets');

// --- Signed release manifest matches the zip ------------------------------------------------
$manifestJson = $read('release-manifest.json');
$manifest = json_decode($manifestJson, true);
if (check(is_array($manifest['files'] ?? null), 'release-manifest.json readable')) {
    check(($manifest['version'] ?? null) === $version, 'manifest version '.($manifest['version'] ?? '?').' matches the code');

    $public = trim($read('release-public-key.txt'));
    $signature = base64_decode(trim($read('release-manifest.sig')), true);
    $key = base64_decode($public, true);
    check(
        $signature !== false && $key !== false && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && sodium_crypto_sign_verify_detached($signature, $manifestJson, $key),
        'manifest signature valid (Ed25519, key '.substr($public, 0, 12).'…)'
    );
    if ($expectedKey !== null) {
        check(hash_equals($expectedKey, $public), 'signed with the expected release key', 'package key '.$public);
    }

    $mismatch = [];
    foreach ($manifest['files'] as $path => $hash) {
        if (! $has($path) || hash('sha256', (string) $zip->getFromIndex($files[$path])) !== $hash) {
            $mismatch[] = $path;
        }
    }
    check($mismatch === [], count($manifest['files']).' manifest entries match the zip content', 'missing or different: '.implode(', ', array_slice($mismatch, 0, 10)));

    // Same exclusions as scripts/build-release.php (runtime-written files).
    $unlisted = array_values(array_filter(array_keys($files), fn ($path) => ! isset($manifest['files'][$path])
        && ! preg_match('#^(storage/|bootstrap/cache/|public/media/(?!\.htaccess$)|\.env$|release-)#', $path)));
    check($unlisted === [], 'every packaged file is covered by the manifest', 'unlisted: '.implode(', ', array_slice($unlisted, 0, 10)));
}
$zip->close();

// --- Checksum file -------------------------------------------------------------------------
$sumFile = $zipPath.'.sha256';
$actual = hash_file('sha256', $zipPath);
if (check(is_file($sumFile), basename($sumFile).' present')) {
    $line = preg_split('/\s+/', trim((string) file_get_contents($sumFile)));
    check(strtolower($line[0] ?? '') === $actual, 'SHA-256 file matches the zip ('.$actual.')', 'file says '.($line[0] ?? '?'));
    check(ltrim($line[1] ?? '', '*') === basename($zipPath), 'SHA-256 file names '.basename($zipPath));
}

finish();
