<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ImportAgeGroups;
use App\Exceptions\MissingImportColumnsException;
use App\Exports\AgeGroupExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportAgeGroupsRequest;
use App\Http\Requests\StoreAgeGroupRequest;
use App\Models\AgeGroup;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgeGroupController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('update', $competition);

        $competition->load('ageGroups');

        return view('admin.competitions.age-groups.index', compact('competition'));
    }

    public function template(Competition $competition): BinaryFileResponse
    {
        $this->authorize('update', $competition);

        return Excel::download(
            new AgeGroupExport($competition),
            'kelompok-umur-'.$competition->slug.'.xlsx',
        );
    }

    public function import(ImportAgeGroupsRequest $request, Competition $competition, ImportAgeGroups $import): RedirectResponse
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
                    'file' => $result['errors'][0] ?? 'Tidak ada kelompok umur yang diimpor.',
                ]);
        }

        $parts = [];

        if ($result['created'] > 0) {
            $parts[] = $result['created'].' kelompok umur dibuat';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' kelompok umur diperbarui';
        }

        return back()
            ->with('status', implode(', ', $parts).'.')
            ->with('import_errors', $result['errors']);
    }

    public function store(StoreAgeGroupRequest $request, Competition $competition): RedirectResponse
    {
        $competition->ageGroups()->create($request->validated());

        return back()->with('status', 'Kelompok umur ditambahkan.');
    }

    public function update(StoreAgeGroupRequest $request, Competition $competition, AgeGroup $ageGroup): RedirectResponse
    {
        abort_unless($ageGroup->competition_id === $competition->id, 404);

        $ageGroup->update($request->validated());

        return back()->with('status', 'Kelompok umur diperbarui.');
    }

    public function destroy(Competition $competition, AgeGroup $ageGroup): RedirectResponse
    {
        $this->authorize('update', $competition);
        abort_unless($ageGroup->competition_id === $competition->id, 404);

        if ($ageGroup->registrations()->exists()) {
            return back()->withErrors([
                'delete' => 'Kelompok umur yang sudah memiliki pendaftaran tidak dapat dihapus.',
            ]);
        }

        $ageGroup->delete();

        return back()->with('status', 'Kelompok umur dihapus.');
    }

    public function quickFill(Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        $existingCodes = $competition->ageGroups()->pluck('code')->all();
        $created = 0;

        foreach (AgeGroup::defaultDefinitions($competition->year()) as $definition) {
            if (in_array($definition['code'], $existingCodes, true)) {
                continue;
            }

            $competition->ageGroups()->create($definition);
            $created++;
        }

        $officialCount = count(AgeGroup::defaultDefinitions($competition->year()));
        $message = $created === 0
            ? "{$officialCount} grup baku sudah ada."
            : "{$created} kelompok umur baku ditambahkan berdasarkan tahun {$competition->year()}.";

        return back()->with('status', $message);
    }
}
