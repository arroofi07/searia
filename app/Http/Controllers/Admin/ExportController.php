<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BlankResultSheetExport;
use App\Exports\MedalTallyExport;
use App\Exports\ParticipantExport;
use App\Exports\PerEventResultExport;
use App\Exports\PerEventStartListExport;
use App\Exports\ResultExport;
use App\Exports\StartListExport;
use App\Http\Controllers\Controller;
use App\Imports\BlankResultSheetImport;
use App\Models\Club;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('seed', $competition);

        return view('admin.exports.index', [
            'competition' => $competition,
            'clubs' => Club::query()->orderBy('name')->get(),
            'events' => $competition->events,
            'statuses' => \App\Enums\RegistrationStatus::cases(),
        ]);
    }

    public function participants(Request $request, Competition $competition): BinaryFileResponse
    {
        $this->authorize('seed', $competition);

        $clubId = $request->filled('club_id') ? $request->integer('club_id') : null;
        $eventId = $request->filled('event_id') ? $request->integer('event_id') : null;
        $status = $request->string('status')->toString() ?: null;

        return Excel::download(
            new ParticipantExport($competition, $clubId, $eventId, $status),
            'peserta-'.$competition->slug.'.xlsx',
        );
    }

    public function startList(Request $request, Competition $competition): BinaryFileResponse
    {
        $this->authorize('seed', $competition);

        $export = $request->boolean('per_event')
            ? new PerEventStartListExport($competition)
            : new StartListExport($competition);

        return Excel::download($export, 'start-list-'.$competition->slug.'.xlsx');
    }

    public function results(Request $request, Competition $competition): BinaryFileResponse
    {
        $this->authorize('seed', $competition);

        $export = $request->boolean('per_event')
            ? new PerEventResultExport($competition)
            : new ResultExport($competition);

        return Excel::download($export, 'hasil-'.$competition->slug.'.xlsx');
    }

    public function medals(Competition $competition): BinaryFileResponse
    {
        $this->authorize('seed', $competition);

        return Excel::download(new MedalTallyExport($competition), 'medali-'.$competition->slug.'.xlsx');
    }

    public function blankResults(Competition $competition): BinaryFileResponse
    {
        $this->authorize('seed', $competition);

        return Excel::download(new BlankResultSheetExport($competition), 'lembar-hasil-'.$competition->slug.'.xlsx');
    }

    public function importBlankResults(Request $request, Competition $competition): RedirectResponse
    {
        $this->authorize('seed', $competition);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new BlankResultSheetImport($competition, $request->user());
        Excel::import($import, $request->file('file'));

        return back()->with('status', $import->imported.' hasil diimpor dari lembar Excel.');
    }
}
