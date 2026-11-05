<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Entry point cPanel (Git Version Control)
|--------------------------------------------------------------------------
|
| Repo Laravel : /home/seaf5528/searia
| Document root: /home/seaf5528/public_html  (file ini jadi index.php di sana)
|
*/

$laravelRoot = __DIR__.'/../searia';

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
