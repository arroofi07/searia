<?php

namespace App\Http\Controllers\Coach;

use App\Enums\ClubStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClubProfileRequest;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClubProfileController extends Controller
{
    public function show(Club $club): View
    {
        $this->authorize('view', $club);

        return view('coach.club.show', compact('club'));
    }

    public function edit(Club $club): View
    {
        $this->authorize('update', $club);

        return view('coach.club.edit', compact('club'));
    }

    public function update(UpdateClubProfileRequest $request, Club $club): RedirectResponse
    {
        $data = $request->safe()->except(['logo']);
        $wasVerified = $club->isVerified();

        if ($request->hasFile('logo')) {
            /** @var UploadedFile $logo */
            $logo = $request->file('logo');
            $this->replaceLogo($club, $logo);
        }

        $club->fill($data);

        if ($wasVerified && $club->isDirty('name')) {
            $club->status = ClubStatus::Pending;
        }

        $club->save();

        $message = $club->status === ClubStatus::Pending && $wasVerified
            ? 'Perubahan nama klub menunggu persetujuan panitia ulang.'
            : 'Profil klub diperbarui.';

        return redirect()
            ->route('coach.club.show', $club)
            ->with('status', $message);
    }

    private function replaceLogo(Club $club, UploadedFile $logo): void
    {
        if ($club->logo_path) {
            Storage::disk('public')->delete($club->logo_path);
        }

        $club->logo_path = $logo->store('clubs/logos', 'public');
    }
}
