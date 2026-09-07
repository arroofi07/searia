<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadedFileGuard
{
    /**
     * @param  list<string>  $allowedMime
     */
    public static function assertSafe(UploadedFile $file, array $allowedMime, int $maxBytes): void
    {
        if ($file->getSize() !== false && $file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran berkas melebihi batas yang diizinkan.',
            ]);
        }

        $mime = (string) ($file->getMimeType() ?: '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = (string) $finfo->file($file->getRealPath());

        if (! in_array($detected, $allowedMime, true) && ! in_array($mime, $allowedMime, true)) {
            throw ValidationException::withMessages([
                'file' => 'Tipe berkas tidak diizinkan.',
            ]);
        }

        // Reject PHP payloads disguised as images/documents.
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 512);
        if (preg_match('/<\?php|<script\s+language\s*=\s*["\']?php/i', $head) === 1) {
            throw ValidationException::withMessages([
                'file' => 'Isi berkas tidak valid.',
            ]);
        }

        if (str_starts_with($detected, 'image/') || str_starts_with($mime, 'image/')) {
            $info = @getimagesize($file->getRealPath());
            if ($info === false) {
                throw ValidationException::withMessages([
                    'file' => 'Berkas gambar tidak valid.',
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $allowedMime
     */
    public static function storePrivate(UploadedFile $file, string $directory, array $allowedMime, int $maxBytes): string
    {
        self::assertSafe($file, $allowedMime, $maxBytes);

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'text/csv', 'text/plain' => 'csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => strtolower($file->getClientOriginalExtension() ?: 'bin'),
        };

        return $file->storeAs($directory, \Illuminate\Support\Str::uuid()->toString().'.'.$extension, 'local');
    }
}
