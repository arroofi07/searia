<?php

namespace App\Support;

use App\Models\Competition;
use Illuminate\Support\Facades\Storage;

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

    /**
     * URL gambar merek bawaan (/brand/...) dengan base path aplikasi.
     */
    public static function brandImageUrl(string $name): string
    {
        $base = '';

        if (! app()->runningInConsole()) {
            $base = rtrim((string) request()->getBasePath(), '/');
        }

        return ($base === '' ? '' : $base).route('brand.image', $name, false);
    }

    /**
     * URL logo kejuaraan untuk halaman publik. Memakai rute Laravel agar tetap
     * berfungsi di produksi tanpa symlink public/storage (cPanel, CapRover, subfolder).
     */
    public static function competitionLogoUrl(Competition $competition): string
    {
        $path = route('public.competitions.logo', $competition, false);
        $base = '';

        if (! app()->runningInConsole()) {
            $base = rtrim((string) request()->getBasePath(), '/');
        }

        $url = ($base === '' ? '' : $base).$path;
        $logoPath = $competition->logo_path;

        if (is_string($logoPath) && $logoPath !== '' && Storage::disk('public')->exists($logoPath)) {
            try {
                $url .= '?v='.Storage::disk('public')->lastModified($logoPath);
            } catch (\Throwable) {
                // Abaikan jika driver tidak mendukung lastModified.
            }
        }

        return $url;
    }
}
