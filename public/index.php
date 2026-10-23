<?php

/*
| Biofy.TR front controller.
| This file stays syntax-compatible with old PHP versions so an unsupported server
| shows a readable message instead of a parse error from the framework.
*/

define('LARAVEL_START', microtime(true));

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Biofy.TR</title><body style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 16px">'
        .'<h1>PHP 8.3 veya üzeri gerekli</h1><p>Sunucunuzda PHP '.htmlspecialchars(PHP_VERSION).' çalışıyor. Hosting panelinizden (cPanel → "Select PHP Version" / Plesk → "PHP Ayarları") PHP 8.3 veya 8.4 seçin.</p>'
        .'<h2>PHP 8.3 or newer is required</h2><p>This server runs PHP '.htmlspecialchars(PHP_VERSION).'. Select PHP 8.3 or 8.4 in your hosting control panel.</p></body>';
    exit;
}

// Application files are either next to public/ (recommended) or in a sibling folder
// when only the public files were copied into public_html (see docs/SHARED_HOSTING.md).
$biofyBase = null;

foreach (array(__DIR__.'/..', __DIR__.'/../biofy', __DIR__.'/../biofy-app') as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php')) {
        $biofyBase = realpath($candidate);
        break;
    }
}

if ($biofyBase === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Biofy.TR application files were not found. See docs/SHARED_HOSTING.md.\n";
    exit;
}

// Not installed yet: run the web installer (it locks itself after a successful install).
if (! is_file($biofyBase.'/storage/app/private/installed.lock')) {
    // Container images install from environment variables (biofy:container-setup), never through the web.
    if (getenv('BIOFY_DISABLE_WEB_INSTALLER')) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        header('Retry-After: 60');
        echo "Biofy.TR is not installed yet. Check the container logs (biofy:container-setup).\n";
        exit;
    }

    define('BIOFY_BASE', $biofyBase);
    define('BIOFY_PUBLIC', __DIR__);
    require $biofyBase.'/install/installer.php';
    exit;
}

if (file_exists($maintenance = $biofyBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $biofyBase.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $biofyBase.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(\Illuminate\Http\Request::capture());
