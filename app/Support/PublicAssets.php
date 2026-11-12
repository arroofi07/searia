<?php

namespace App\Support;

class PublicAssets
{
    public static function cssUrl(): string
    {
        $path = public_path('css/app.css');
        $version = is_file($path) ? (string) filemtime($path) : '1';
        $base = '/';

        if (! app()->runningInConsole()) {
            $base = rtrim((string) request()->getBasePath(), '/').'/';
        }

        return $base.'css/app.css?v='.$version;
    }

    /**
     * URL berkas di disk `public` (symlink `public/storage`), mengikuti base path request.
     */
    public static function storageUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $base = '';

        if (! app()->runningInConsole()) {
            $base = rtrim((string) request()->getBasePath(), '/');
        }

        return ($base === '' ? '' : $base).'/storage/'.$path;
    }
}
