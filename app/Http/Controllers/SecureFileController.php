<?php

namespace App\Http\Controllers;

use App\Models\Athlete;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function athletePhoto(Athlete $athlete): StreamedResponse
    {
        $this->authorize('view', $athlete);
        abort_unless(filled($athlete->photo_path) && Storage::disk('local')->exists($athlete->photo_path), 404);

        return Storage::disk('local')->response($athlete->photo_path);
    }
}
