<?php
/**
 * Diagnosa cepat cPanel. Upload/salin ke public_html lalu buka:
 *   https://domain-anda/cpanel-check.php
 * HAPUS file ini setelah selesai.
 */

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = __DIR__.'/../searia';
$checks = [];

$checks['PHP_VERSION'] = PHP_VERSION;
$checks['PHP_OK_(>=8.2)'] = version_compare(PHP_VERSION, '8.2.0', '>=') ? 'YES' : 'NO — naikkan PHP di MultiPHP Manager';
$checks['__DIR___(public_html)'] = __DIR__;
$checks['laravelRoot'] = $laravelRoot;
$checks['laravelRoot_exists'] = is_dir($laravelRoot) ? 'YES' : 'NO';
$checks['vendor_autoload'] = is_file($laravelRoot.'/vendor/autoload.php') ? 'YES' : 'NO — upload folder vendor';
$checks['bootstrap_app'] = is_file($laravelRoot.'/bootstrap/app.php') ? 'YES' : 'NO';
$checks['env_file'] = is_file($laravelRoot.'/.env') ? 'YES' : 'NO — buat .env di folder searia';
$checks['storage_writable'] = is_dir($laravelRoot.'/storage') && is_writable($laravelRoot.'/storage') ? 'YES' : 'NO';
$checks['bootstrap_cache_writable'] = is_dir($laravelRoot.'/bootstrap/cache') && is_writable($laravelRoot.'/bootstrap/cache') ? 'YES' : 'NO';
$checks['maintenance_mode'] = is_file($laravelRoot.'/storage/framework/maintenance.php') ? 'YES (matikan maintenance)' : 'NO';
$checks['index_is_cpanel'] = (is_file(__DIR__.'/index.php') && str_contains((string) file_get_contents(__DIR__.'/index.php'), '../searia'))
    ? 'YES'
    : 'NO — index.php belum versi cPanel';

foreach ($checks as $k => $v) {
    echo str_pad($k, 32).': '.$v.PHP_EOL;
}

echo PHP_EOL.'--- require test ---'.PHP_EOL;

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    echo "STOP: vendor belum ada\n";
    exit;
}

try {
    require $laravelRoot.'/vendor/autoload.php';
    echo "autoload: OK\n";
    $app = require $laravelRoot.'/bootstrap/app.php';
    echo 'bootstrap: OK ('.get_class($app).")\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
