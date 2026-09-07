<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ParticipantTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\ImportBatch;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(Competition $competition): BinaryFileResponse
    {
        $this->authorize('create', ImportBatch::class);

        $filename = 'template-'.$competition->slug.'.xlsx';

        return Excel::download(new ParticipantTemplateExport($competition), $filename);
    }
}
