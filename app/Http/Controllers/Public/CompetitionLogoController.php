<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetitionLogoController extends Controller
{
    public function __invoke(Competition $competition): StreamedResponse
    {
        $path = $competition->logo_path;
        abort_unless(is_string($path) && $path !== '', 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, headers: [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
