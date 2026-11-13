<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class JudgeScriptController extends Controller
{
    public function __invoke(string $script): Response
    {
        $files = [
            'heat-autosave.js' => 'heat-autosave.js',
            'swim-time-input.js' => 'swim-time-input.js',
        ];

        abort_unless(isset($files[$script]), 404);

        $path = resource_path('js/'.$files[$script]);
        abort_unless(is_file($path), 404);

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
