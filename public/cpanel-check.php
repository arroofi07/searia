<?php
/**
 * Diagnosa cPanel. Letakkan di public_html, buka:
 *   https://seariaquatic.com/cpanel-check.php
 * HAPUS setelah selesai.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = realpath(__DIR__.'/../website/searia') ?: (__DIR__.'/../website/searia');

echo "=== PATH ===\n";
echo 'public_html : '.__DIR__."\n";
echo 'laravelRoot : '.$laravelRoot."\n";
echo 'resolved    : '.(is_dir($laravelRoot) ? 'DIR OK' : 'DIR MISSING')."\n\n";

echo "=== PHP ===\n";
echo 'version : '.PHP_VERSION.(version_compare(PHP_VERSION, '8.2.0', '>=') ? " OK\n" : " NEED >= 8.2\n");
foreach (['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl'] as $ext) {
    echo 'ext '.$ext.' : '.(extension_loaded($ext) ? 'YES' : 'MISSING')."\n";
}

echo "\n=== FILES ===\n";
$files = [
    'vendor/autoload.php',
    'bootstrap/app.php',
    '.env',
    'storage/logs',
    'bootstrap/cache',
];
foreach ($files as $rel) {
    $path = $laravelRoot.'/'.$rel;
    $ok = file_exists($path) || is_dir($path);
    $w = (is_dir($path) ? (is_writable($path) ? ' writable' : ' NOT writable') : '');
    echo str_pad($rel, 24).': '.($ok ? 'YES'.$w : 'NO')."\n";
}

$index = (string) @file_get_contents(__DIR__.'/index.php');
echo 'index.php has /../website/searia : '.(str_contains($index, '/../website/searia') ? 'YES' : 'NO — path index salah')."\n";

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    echo "\nSTOP: upload vendor/ dulu\n";
    exit;
}

echo "\n=== BOOT LARAVEL ===\n";
try {
    require $laravelRoot.'/vendor/autoload.php';
    echo "autoload OK\n";

    /** @var \Illuminate\Foundation\Application $app */
    $app = require $laravelRoot.'/bootstrap/app.php';
    $app->usePublicPath(__DIR__);
    echo 'bootstrap OK ('.$app->version().")\n";

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    echo 'HTTP status : '.$response->getStatusCode()."\n";
    if ($response->getStatusCode() >= 500) {
        echo "\n--- body (awal) ---\n";
        echo substr($response->getContent() ?: '', 0, 2000)."\n";
    } else {
        echo "Request OK — masalah mungkin sudah teratasi, coba buka beranda.\n";
    }
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo "EXCEPTION: ".$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString()."\n";
}

$log = $laravelRoot.'/storage/logs/laravel.log';
if (is_file($log)) {
    echo "\n=== laravel.log (ekor) ===\n";
    $lines = @file($log);
    if (is_array($lines)) {
        echo implode('', array_slice($lines, -40));
    }
}
