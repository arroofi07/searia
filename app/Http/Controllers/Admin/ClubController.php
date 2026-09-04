<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectClubRequest;
use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\UpdateClubRequest;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Club::class);

        $clubs = Club::query()
            ->withCount('athletes')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.mb_strtolower((string) $request->string('search')).'%';
                $query->whereRaw('LOWER(name) LIKE ?', [$search]);
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $cities = Club::query()->select('city')->distinct()->orderBy('city')->pluck('city');

        return view('admin.clubs.index', [
            'clubs' => $clubs,
            'cities' => $cities,
            'filters' => $request->only(['search', 'status', 'city']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Club::class);

        return view('admin.clubs.create');
    }

    public function store(StoreClubRequest $request): RedirectResponse
    {
        $club = Club::query()->create([
            ...$request->validated(),
            'status' => ClubStatus::Verified,
        ]);

        return redirect()
            ->route('admin.clubs.show', $club)
            ->with('status', 'Klub berhasil ditambahkan.');
    }

    public function show(Club $club): View
    {
        $this->authorize('view', $club);

        $club->loadCount('athletes');

        return view('admin.clubs.show', compact('club'));
    }

    public function edit(Club $club): View
    {
        $this->authorize('update', $club);

        return view('admin.clubs.edit', compact('club'));
    }

    public function update(UpdateClubRequest $request, Club $club): RedirectResponse
    {
        $club->update($request->validated());

        return redirect()
            ->route('admin.clubs.show', $club)
            ->with('status', 'Data klub diperbarui.');
    }

    public function destroy(Club $club): RedirectResponse
    {
        $this->authorize('delete', $club);

        if ($club->athletes()->exists()) {
            $club->update(['is_active' => false]);

            return back()->withErrors([
                'delete' => 'Klub yang sudah memiliki atlet tidak dapat dihapus, hanya dapat dinonaktifkan.',
            ]);
        }

        $club->delete();

        return redirect()
            ->route('admin.clubs.index')
            ->with('status', 'Klub dihapus.');
    }

    public function verify(Club $club): RedirectResponse
    {
        $this->authorize('verify', $club);

        $club->update([
            'status' => ClubStatus::Verified,
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Klub disetujui dan berstatus terverifikasi.');
    }

    public function reject(RejectClubRequest $request, Club $club): RedirectResponse
    {
        $club->update([
            'status' => ClubStatus::Rejected,
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        return back()->with('status', 'Klub ditolak.');
    }
}
