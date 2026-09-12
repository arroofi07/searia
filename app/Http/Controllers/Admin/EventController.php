<?php

namespace App\Http\Controllers\Admin;

use App\Actions\FillDefaultProgram;
use App\Actions\ImportEventProgram;
use App\Enums\EventGender;
use App\Exceptions\MissingImportColumnsException;
use App\Exports\EventProgramExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportEventProgramRequest;
use App\Http\Requests\ReorderEventsRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Competition;
use App\Models\Event;
use App\Services\ProgramOrderBuilder;
use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventController extends Controller
{
    public function index(Competition $competition, ProgramOrderBuilder $program): View
    {
        $this->authorize('update', $competition);

        $competition->load('events');

        return view('admin.competitions.events.index', [
            'competition' => $competition,
            'programRows' => $program->rows($competition->events),
        ]);
    }

    public function quickFill(Competition $competition, FillDefaultProgram $fill): RedirectResponse
    {
        $this->authorize('update', $competition);

        $created = $fill->handle($competition);

        $message = $created === 0
            ? 'Susunan acara baku sudah lengkap (34 nomor).'
            : "{$created} nomor acara baku ditambahkan sesuai susunan PA/PI.";

        return back()->with('status', $message);
    }

    public function template(Competition $competition): BinaryFileResponse
    {
        $this->authorize('update', $competition);

        return Excel::download(
            new EventProgramExport($competition),
            'nomor-lomba-'.$competition->slug.'.xlsx',
        );
    }

    public function import(ImportEventProgramRequest $request, Competition $competition, ImportEventProgram $import): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file?->getRealPath();

        if ($file === null || $path === false || $path === '') {
            return back()->withErrors(['file' => 'Berkas tidak dapat dibaca.']);
        }

        try {
            $result = $import->handle($competition, $path, $file->getClientOriginalExtension());
        } catch (MissingImportColumnsException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        if ($result['created'] === 0 && $result['updated'] === 0) {
            return back()
                ->with('import_errors', $result['errors'])
                ->withErrors([
                    'file' => $result['errors'][0] ?? 'Tidak ada nomor lomba yang diimpor.',
                ]);
        }

        $parts = [];

        if ($result['created'] > 0) {
            $parts[] = $result['created'].' nomor ditambahkan';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' nomor diperbarui';
        }

        return back()
            ->with('status', implode(', ', $parts).'.')
            ->with('import_errors', $result['errors']);
    }

    public function store(StoreEventRequest $request, Competition $competition): RedirectResponse
    {
        $data = $request->safe()->except(['create_pair']);
        $data['sort_order'] = ((int) $competition->events()->max('sort_order')) + 1;

        try {
            DB::transaction(function () use ($competition, $data, $request): void {
                $event = $competition->events()->create($data);

                if ($request->boolean('create_pair')) {
                    $event->gender = EventGender::Male;
                    $event->save();

                    $partner = $event->replicate();
                    $partner->event_number = $event->event_number + 1;
                    $partner->gender = EventGender::Female;
                    $partner->sort_order = $event->sort_order + 1;
                    $partner->save();
                }
            });
        } catch (QueryException $exception) {
            if (DatabaseError::isUniqueViolation($exception)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'event_number' => 'Nomor acara ini sudah dipakai di kejuaraan ini.',
                    ]);
            }

            throw $exception;
        }

        $message = $request->boolean('create_pair')
            ? 'Nomor putra dan putri berhasil ditambahkan.'
            : 'Nomor lomba ditambahkan.';

        return back()->with('status', $message);
    }

    public function update(StoreEventRequest $request, Competition $competition, Event $event): RedirectResponse
    {
        abort_unless($event->competition_id === $competition->id, 404);

        try {
            $event->update($request->safe()->except(['create_pair']));
        } catch (QueryException $exception) {
            if (DatabaseError::isUniqueViolation($exception)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'event_number' => 'Nomor acara ini sudah dipakai di kejuaraan ini.',
                    ]);
            }

            throw $exception;
        }

        return back()->with('status', 'Nomor lomba diperbarui.');
    }

    public function destroy(Competition $competition, Event $event): RedirectResponse
    {
        $this->authorize('update', $competition);
        abort_unless($event->competition_id === $competition->id, 404);

        if ($event->registrations()->exists()) {
            $event->update(['is_active' => false]);

            return back()->withErrors([
                'delete' => 'Nomor lomba yang sudah memiliki pendaftaran tidak dapat dihapus, hanya dinonaktifkan.',
            ]);
        }

        $event->delete();

        return back()->with('status', 'Nomor lomba dihapus.');
    }

    public function reorder(ReorderEventsRequest $request, Competition $competition): JsonResponse
    {
        foreach ($request->validated('order') as $position => $eventId) {
            Event::query()
                ->where('competition_id', $competition->id)
                ->where('id', $eventId)
                ->update(['sort_order' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
