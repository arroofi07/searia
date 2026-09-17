<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandImageController extends Controller
{
    /** @var array<string, string> */
    private const FILES = [
        'logo' => 'logo.png',
        'event-logo' => 'event-logo.jpg',
        'apple-touch-icon' => 'apple-touch-icon.png',
        'favicon' => 'favicon.svg',
    ];

    public function __invoke(string $name): BinaryFileResponse
    {
        $file = self::FILES[$name] ?? null;
        abort_unless(is_string($file), 404);

        foreach ([
            resource_path('images/'.$file),
            public_path('images/'.$file),
            public_path($file),
        ] as $path) {
            if (is_file($path)) {
                return response()->file($path, [
                    'Cache-Control' => 'public, max-age=604800',
                ]);
            }
        }

        abort(404);
    }
}
