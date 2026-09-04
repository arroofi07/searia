<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAthleteRequest;
use App\Http\Requests\UpdateAthleteRequest;
use App\Models\Athlete;
use App\Models\Club;
use App\Services\AthleteMatcher;
use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Athlete::class);

        $query = Athlete::query()
            ->with('club')
            ->when(
                $request->user()?->cannot('manageAny', Athlete::class),
                fn ($builder) => $builder->where('club_id', $request->user()?->club_id),
            )
            ->when($request->filled('search'), function ($builder) use ($request): void {
                $search = '%'.mb_strtolower((string) $request->string('search')).'%';
                $builder->whereRaw('LOWER(full_name) LIKE ?', [$search]);
            })
            ->when($request->filled('birth_year'), fn ($builder) => $builder->where('birth_year', $request->integer('birth_year')))
            ->when($request->filled('gender'), fn ($builder) => $builder->where('gender', $request->string('gender')))
            ->when($request->filled('club_id') && $request->user()?->can('manageAny', Athlete::class), function ($builder) use ($request): void {
                $builder->where('club_id', $request->integer('club_id'));
            })
            ->orderBy('full_name');

        return view('athletes.index', [
            'athletes' => $query->paginate(20)->withQueryString(),
            'clubs' => $request->user()?->can('manageAny', Athlete::class)
                ? Club::query()->orderBy('name')->get()
                : collect(),
            'filters' => $request->only(['search', 'birth_year', 'gender', 'club_id']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Athlete::class);

        return view('athletes.create', [
            'clubs' => $this->clubsForForm($request),
            'lockedClub' => $request->user()?->cannot('manageAny', Athlete::class)
                ? $request->user()?->club
                : null,
        ]);
    }

    public function store(StoreAthleteRequest $request, AthleteMatcher $matcher): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);

        try {
            $athlete = Athlete::query()->create($data);
        } catch (QueryException $exception) {
            if (DatabaseError::isUniqueViolation($exception)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'full_name' => 'Atlet dengan nama dan tahun lahir yang sama sudah ada di klub ini.',
                    ]);
            }

            throw $exception;
        }

        if ($request->hasFile('photo')) {
            /** @var UploadedFile $photo */
            $photo = $request->file('photo');
            $athlete->update([
                'photo_path' => $photo->store('athletes/photos', 'public'),
            ]);
        }

        $similar = $matcher->findSimilar($athlete);

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', 'Atlet berhasil ditambahkan.')
            ->with('similar_athletes', $similar->pluck('id')->all());
    }

    public function show(Athlete $athlete, AthleteMatcher $matcher): View
    {
        $this->authorize('view', $athlete);

        $athlete->load('club');

        $similar = $matcher->findSimilar($athlete);
        $sessionIds = collect(session('similar_athletes', []));

        if ($sessionIds->isNotEmpty()) {
            $similar = $similar->merge(
                Athlete::query()->whereIn('id', $sessionIds)->get(),
            )->unique('id')->values();
        }

        return view('athletes.show', [
            'athlete' => $athlete,
            'similarAthletes' => $similar,
        ]);
    }

    public function edit(Request $request, Athlete $athlete): View
    {
        $this->authorize('update', $athlete);

        return view('athletes.edit', [
            'athlete' => $athlete,
            'clubs' => $this->clubsForForm($request),
            'lockedClub' => $request->user()?->cannot('manageAny', Athlete::class)
                ? $athlete->club
                : null,
        ]);
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);

        if ($request->hasFile('photo')) {
            /** @var UploadedFile $photo */
            $photo = $request->file('photo');

            if ($athlete->photo_path) {
                Storage::disk('public')->delete($athlete->photo_path);
            }

            $data['photo_path'] = $photo->store('athletes/photos', 'public');
        }

        try {
            $athlete->update($data);
        } catch (QueryException $exception) {
            if (DatabaseError::isUniqueViolation($exception)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'full_name' => 'Atlet dengan nama dan tahun lahir yang sama sudah ada di klub ini.',
                    ]);
            }

            throw $exception;
        }

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('status', 'Data atlet diperbarui.');
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        $this->authorize('delete', $athlete);

        if ($athlete->registrations()->exists()) {
            $athlete->update(['is_active' => false]);

            return back()->with('status', 'Atlet pernah didaftarkan pada kejuaraan, data dinonaktifkan.');
        }

        if ($athlete->photo_path) {
            Storage::disk('public')->delete($athlete->photo_path);
        }

        $athlete->delete();

        return redirect()
            ->route('athletes.index')
            ->with('status', 'Atlet dihapus.');
    }

    /**
     * @return Collection<int, Club>
     */
    private function clubsForForm(Request $request): Collection
    {
        if ($request->user()?->cannot('manageAny', Athlete::class)) {
            return collect();
        }

        return Club::query()->orderBy('name')->get();
    }
}
