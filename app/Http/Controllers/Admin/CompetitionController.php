<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Models\Competition;
use App\Services\CompetitionDuplicator;
use App\Services\CompetitionReadinessCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Competition::class);

        $competitions = Competition::query()
            ->withCount(['ageGroups', 'events', 'registrations'])
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('admin.competitions.index', compact('competitions'));
    }

    public function create(): View
    {
        $this->authorize('create', Competition::class);

        return view('admin.competitions.create');
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $competition = Competition::query()->create([
            ...$request->validated(),
            'status' => CompetitionStatus::Draft,
        ]);

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('status', 'Kejuaraan berhasil dibuat.');
    }

    public function show(Competition $competition, CompetitionReadinessCheck $readiness): View
    {
        $this->authorize('view', $competition);

        $competition->loadCount(['ageGroups', 'events', 'registrations']);

        return view('admin.competitions.show', [
            'competition' => $competition,
            'findings' => $readiness->findings($competition),
            'ready' => $readiness->isReady($competition),
        ]);
    }

    public function edit(Competition $competition): View
    {
        $this->authorize('update', $competition);

        return view('admin.competitions.edit', compact('competition'));
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $competition->update($request->validated());

        return redirect()
            ->route('admin.competitions.show', $competition)
            ->with('status', 'Data kejuaraan diperbarui.');
    }

    public function duplicate(Competition $competition, CompetitionDuplicator $duplicator): RedirectResponse
    {
        $this->authorize('duplicate', $competition);

        $copy = $duplicator->duplicate($competition);

        return redirect()
            ->route('admin.competitions.show', $copy)
            ->with('status', 'Kejuaraan digandakan. Pendaftaran tidak ikut tersalin.');
    }
}
