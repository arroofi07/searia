<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidSwimTimeException;
use App\Http\Requests\StoreRegistrationAthleteRequest;
use App\Http\Requests\StoreRegistrationEventsRequest;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Services\AgeGroupResolver;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use App\Services\SeedTimeSuggester;
use App\Support\DatabaseError;
use App\Support\SwimTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        $competitions = Competition::query()
            ->where('status', \App\Enums\CompetitionStatus::Registration)
            ->orderBy('start_date')
            ->get();

        return view('registrations.index', compact('competitions'));
    }

    public function create(Request $request, Competition $competition, AgeGroupResolver $resolver): View
    {
        $this->authorize('create', Registration::class);
        abort_unless($competition->isOpenForRegistration(), 403, 'Pendaftaran sudah ditutup');

        $athletes = Athlete::query()
            ->with('club')
            ->where('is_active', true)
            ->when(
                $request->user()?->cannot('manageAny', Athlete::class),
                fn ($query) => $query->where('club_id', $request->user()?->club_id),
            )
            ->orderBy('full_name')
            ->get();

        $wizard = $this->wizard($competition);
        $selected = isset($wizard['athlete_id'])
            ? $athletes->firstWhere('id', $wizard['athlete_id'])
            : null;
        $ageGroup = $selected
            ? $resolver->resolve($competition, $selected->birth_year)
            : null;

        return view('registrations.create', [
            'competition' => $competition,
            'athletes' => $athletes,
            'selected' => $selected,
            'ageGroup' => $ageGroup,
        ]);
    }

    public function storeAthlete(StoreRegistrationAthleteRequest $request, Competition $competition, AgeGroupResolver $resolver): RedirectResponse
    {
        abort_unless($competition->isOpenForRegistration(), 403);

        $athlete = $request->athlete();
        $this->assertAthleteAccess($request, $athlete);

        $ageGroup = $resolver->resolve($competition, $athlete->birth_year);

        if ($ageGroup === null) {
            return back()->withInput()->withErrors([
                'athlete_id' => 'Usia atlet di luar rentang kejuaraan ini',
            ]);
        }

        $this->putWizard($competition, [
            'athlete_id' => $athlete->id,
            'event_ids' => [],
            'seed_times' => [],
        ]);

        return redirect()->route('registrations.events', $competition);
    }

    public function events(
        Request $request,
        Competition $competition,
        AgeGroupResolver $resolver,
        SeedTimeSuggester $suggester,
    ): View|RedirectResponse {
        $this->authorize('create', Registration::class);

        $wizard = $this->wizard($competition);
        $athlete = Athlete::query()->find($wizard['athlete_id'] ?? null);

        if (! $athlete instanceof Athlete) {
            return redirect()->route('registrations.create', $competition);
        }

        $this->assertAthleteAccess($request, $athlete);
        $ageGroup = $resolver->resolve($competition, $athlete->birth_year);

        if ($ageGroup === null) {
            return redirect()->route('registrations.create', $competition)
                ->withErrors(['athlete_id' => 'Usia atlet di luar rentang kejuaraan ini']);
        }

        $events = $competition->events()
            ->with('ageGroups')
            ->where('gender', $athlete->gender->eventGender())
            ->where('is_active', true)
            ->get()
            ->filter(fn (Event $event): bool => $event->ageGroups->contains('id', $ageGroup->id))
            ->values();

        $suggestions = [];
        foreach ($events as $event) {
            $suggestions[$event->id] = $suggester->suggest($athlete, $event, $competition);
        }

        $used = Registration::query()
            ->where('competition_id', $competition->id)
            ->where('athlete_id', $athlete->id)
            ->get()
            ->filter(fn (Registration $registration): bool => $registration->status->countsTowardQuota())
            ->count();

        return view('registrations.events', [
            'competition' => $competition,
            'athlete' => $athlete,
            'ageGroup' => $ageGroup,
            'events' => $events,
            'wizard' => $wizard,
            'suggestions' => $suggestions,
            'used' => $used,
        ]);
    }

    public function storeEvents(StoreRegistrationEventsRequest $request, Competition $competition): RedirectResponse
    {
        abort_unless($competition->isOpenForRegistration(), 403);

        $wizard = $this->wizard($competition);

        if (! isset($wizard['athlete_id'])) {
            return redirect()->route('registrations.create', $competition);
        }

        $this->putWizard($competition, [
            ...$wizard,
            'event_ids' => $request->validated('event_ids'),
            'seed_times' => $request->validated('seed_times') ?? [],
        ]);

        return redirect()->route('registrations.review', $competition);
    }

    public function review(Request $request, Competition $competition, AgeGroupResolver $resolver): View|RedirectResponse
    {
        $this->authorize('create', Registration::class);

        $wizard = $this->wizard($competition);
        $athlete = Athlete::query()->with('club')->find($wizard['athlete_id'] ?? null);
        $eventIds = $wizard['event_ids'] ?? [];

        if (! $athlete instanceof Athlete || $eventIds === []) {
            return redirect()->route('registrations.create', $competition);
        }

        $ageGroup = $resolver->resolve($competition, $athlete->birth_year);
        $events = Event::query()->whereIn('id', $eventIds)->orderBy('event_number')->get();
        $seedTimes = $wizard['seed_times'] ?? [];
        $parsed = [];

        foreach ($events as $event) {
            $input = $seedTimes[$event->id] ?? $seedTimes[(string) $event->id] ?? null;
            try {
                $parsed[$event->id] = SwimTime::parse(is_string($input) ? $input : null);
            } catch (InvalidSwimTimeException) {
                $parsed[$event->id] = false;
            }
        }

        $token = (string) Str::uuid();
        session(['registration_token.'.$competition->id => $token]);

        return view('registrations.review', [
            'competition' => $competition,
            'athlete' => $athlete,
            'ageGroup' => $ageGroup,
            'events' => $events,
            'seedTimes' => $seedTimes,
            'parsed' => $parsed,
            'token' => $token,
            'totalFee' => $events->count() * $competition->fee_per_event,
        ]);
    }

    public function store(
        StoreRegistrationRequest $request,
        Competition $competition,
        AgeGroupResolver $resolver,
        RegistrationValidator $validator,
    ): RedirectResponse {
        abort_unless($competition->isOpenForRegistration(), 403);

        $expected = session('registration_token.'.$competition->id);
        $token = $request->validated('submit_token');

        if (! is_string($expected) || $token !== $expected) {
            return back()->withErrors(['submit_token' => 'Pengiriman tidak valid. Ulangi dari ringkasan.']);
        }

        $cacheKey = 'registration-submit:'.$token;

        if (! Cache::add($cacheKey, true, 600)) {
            return redirect()
                ->route('coach.registrations.index', $competition)
                ->with('status', 'Pendaftaran sudah dikirim.');
        }

        $wizard = $this->wizard($competition);
        $athleteId = $request->integer('athlete_id') ?: ($wizard['athlete_id'] ?? null);
        $eventIds = $request->input('event_ids') ?: ($wizard['event_ids'] ?? []);
        $seedTimes = $request->input('seed_times') ?: ($wizard['seed_times'] ?? []);

        $athlete = Athlete::query()->with('club')->findOrFail($athleteId);
        $this->assertAthleteAccess($request, $athlete);

        $events = Event::query()->with('ageGroups')->whereIn('id', $eventIds)->get();
        $ageGroup = $resolver->resolve($competition, $athlete->birth_year);

        $drafts = $events->map(function (Event $event) use ($competition, $athlete, $seedTimes): RegistrationDraft {
            $input = $seedTimes[$event->id] ?? $seedTimes[(string) $event->id] ?? null;

            return new RegistrationDraft(
                $competition,
                $athlete,
                $event,
                is_string($input) ? $input : null,
            );
        })->all();

        $errorBag = $validator->validateMany($drafts, $request->user());
        $flat = collect($errorBag)->flatten(1);

        if ($flat->isNotEmpty()) {
            Cache::forget($cacheKey);

            return back()->withErrors([
                'events' => $flat->pluck('message')->unique()->implode(' '),
            ]);
        }

        try {
            DB::transaction(function () use ($drafts, $ageGroup, $request): void {
                foreach ($drafts as $draft) {
                    Registration::query()->create([
                        'competition_id' => $draft->competition->id,
                        'event_id' => $draft->event->id,
                        'athlete_id' => $draft->athlete->id,
                        'age_group_id' => $ageGroup?->id,
                        'seed_time_ms' => SwimTime::parse($draft->seedTimeInput)?->milliseconds,
                        'status' => \App\Enums\RegistrationStatus::Pending,
                        'registered_by' => $request->user()?->id,
                    ]);
                }
            });
        } catch (QueryException $exception) {
            if (DatabaseError::isUniqueViolation($exception)) {
                return redirect()
                    ->route('coach.registrations.index', $competition)
                    ->with('status', 'Pendaftaran sudah tercatat.');
            }

            throw $exception;
        }

        session()->forget($this->wizardKey($competition));
        session()->forget('registration_token.'.$competition->id);

        return redirect()
            ->route('coach.registrations.index', $competition)
            ->with('status', count($drafts).' nomor lomba berhasil dikirim.');
    }

    public function parseTime(Request $request): JsonResponse
    {
        $this->authorize('create', Registration::class);

        try {
            $parsed = SwimTime::parse($request->string('input')->toString());
        } catch (InvalidSwimTimeException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'ms' => $parsed?->milliseconds,
            'formatted' => $parsed?->format() ?? 'NT',
        ]);
    }

    public function suggestSeedTime(
        Request $request,
        Competition $competition,
        Event $event,
        SeedTimeSuggester $suggester,
    ): JsonResponse {
        $this->authorize('create', Registration::class);

        abort_unless($event->competition_id === $competition->id, 404);

        $athlete = Athlete::query()->findOrFail($request->integer('athlete_id'));
        $this->assertAthleteAccess($request, $athlete);
        $ms = $suggester->suggest($athlete, $event, $competition);

        return response()->json([
            'ms' => $ms,
            'formatted' => $ms === null ? null : SwimTime::formatMilliseconds($ms),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function wizard(Competition $competition): array
    {
        return session($this->wizardKey($competition), []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function putWizard(Competition $competition, array $data): void
    {
        session([$this->wizardKey($competition) => $data]);
    }

    private function wizardKey(Competition $competition): string
    {
        return 'registration_wizard.'.$competition->id;
    }

    private function assertAthleteAccess(Request $request, Athlete $athlete): void
    {
        if ($request->user()?->cannot('view', $athlete)) {
            abort(403);
        }
    }
}
