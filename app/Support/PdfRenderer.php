<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Response;
use RuntimeException;

class PdfRenderer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function load(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): DomPdf
    {
        self::prepare();

        if (! array_key_exists('logoPath', $data)) {
            $data['logoPath'] = self::logoDataUri();
        }

        try {
            return Pdf::loadView($view, $data)->setPaper($paper, $orientation);
        } catch (RuntimeException $exception) {
            if (! str_contains($exception->getMessage(), 'Cannot resolve public path')) {
                throw $exception;
            }

            config(['dompdf.public_path' => base_path()]);
            app()->forgetInstance('dompdf');
            app()->forgetInstance('dompdf.wrapper');
            app()->forgetInstance('dompdf.options');

            return Pdf::loadView($view, $data)->setPaper($paper, $orientation);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function download(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): Response
    {
        return self::load($view, $data, $paper, $orientation)->download($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function stream(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): Response
    {
        return self::load($view, $data, $paper, $orientation)->stream($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function output(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): string
    {
        return self::load($view, $data, $paper, $orientation)->output();
    }

    public static function prepare(): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $fonts = self::ensureFontsDirectory();
        $public = self::resolvedPublicPath();
        $chroot = array_values(array_unique(array_filter([
            realpath(base_path()) ?: base_path(),
            realpath($public) ?: $public,
            realpath(storage_path()) ?: storage_path(),
            realpath($fonts) ?: $fonts,
            realpath(base_path('vendor/dompdf/dompdf')) ?: null,
            realpath(sys_get_temp_dir()) ?: sys_get_temp_dir(),
        ])));

        config([
            'dompdf.public_path' => $public,
            'dompdf.options.chroot' => $chroot,
            'dompdf.options.font_dir' => $fonts,
            'dompdf.options.font_cache' => $fonts,
            'dompdf.options.log_output_file' => false,
            'dompdf.options.logOutputFile' => false,
            'dompdf.options.enable_remote' => false,
        ]);

        app()->forgetInstance('dompdf');
        app()->forgetInstance('dompdf.wrapper');
        app()->forgetInstance('dompdf.options');
    }

    public static function logoDataUri(): ?string
    {
        $configured = config('searia.pdf.organizer_logo');
        $candidates = [];

        if (is_string($configured) && $configured !== '') {
            $candidates[] = $configured;
        }

        $candidates[] = public_path('images/event-logo.jpg');
        $candidates[] = public_path('images/logo.png');

        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (is_string($documentRoot) && $documentRoot !== '') {
            $candidates[] = $documentRoot.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'event-logo.jpg';
            $candidates[] = $documentRoot.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png';
        }

        $publicHtml = dirname(base_path()).DIRECTORY_SEPARATOR.'public_html';
        $candidates[] = $publicHtml.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'event-logo.jpg';
        $candidates[] = $publicHtml.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png';

        foreach ($candidates as $path) {
            $uri = self::fileDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        return null;
    }

    public static function fileDataUri(string $path): ?string
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    public static function ensureFontsDirectory(): string
    {
        $candidates = [
            storage_path('fonts'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'searia-dompdf-fonts',
        ];

        foreach ($candidates as $fonts) {
            if (! is_dir($fonts)) {
                @mkdir($fonts, 0775, true);
            }

            if (is_dir($fonts) && is_writable($fonts)) {
                return $fonts;
            }
        }

        return sys_get_temp_dir();
    }

    public static function resolvedPublicPath(): string
    {
        $candidates = [
            public_path(),
            $_SERVER['DOCUMENT_ROOT'] ?? null,
            base_path('public'),
            base_path('../public_html'),
            dirname(base_path()).DIRECTORY_SEPARATOR.'public_html',
            base_path(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $real = realpath($candidate);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }

        return base_path();
    }
}
