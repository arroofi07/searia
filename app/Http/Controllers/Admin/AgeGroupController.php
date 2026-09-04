<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgeGroupRequest;
use App\Models\AgeGroup;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AgeGroupController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('update', $competition);

        $competition->load('ageGroups');

        return view('admin.competitions.age-groups.index', compact('competition'));
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

        $message = $created === 0
            ? 'Enam grup baku sudah ada.'
            : "{$created} kelompok umur baku ditambahkan berdasarkan tahun {$competition->year()}.";

        return back()->with('status', $message);
    }
}
