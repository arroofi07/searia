<?php

namespace App\Http\Controllers\Public;

use App\Actions\SubmitPublicRegistration;
use App\Enums\CompetitionStatus;
use App\Exceptions\InvalidSwimTimeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreRegistrantRequest;
use App\Http\Requests\Public\StoreRegistrationEventsRequest;
use App\Http\Requests\Public\SubmitRegistrationRequest;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Services\AgeGroupResolver;
use App\Services\Registration\PublicRegistrationWizard;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use App\Services\SeedTimeSuggester;
use App\Support\SwimTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pendaftaran peserta berjalan tanpa akun. Panitia yang login; pendaftar cukup
 * mengisi tiga langkah lalu menerima kode pendaftaran.
 */
class RegistrationController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::query()
            ->where('status', CompetitionStatus::Registration)
            ->orderBy('start_date')
            ->get();

        return view('register.index', compact('competitions'));
    }

    public function create(Competition $competition, PublicRegistrationWizard $wizard): View
    {
        $this->assertOpen($competition);

        return view('register.create', [
            'competition' => $competition,
            'state' => $wizard->state($competition),
        ]);
    }

    public function storeRegistrant(
        StoreRegistrantRequest $request,
        Competition $competition,
        PublicRegistrationWizard $wizard,
        AgeGroupResolver $resolver,
    ): RedirectResponse {
        $this->assertOpen($competition);

        $data = $request->validated();
        $ageGroup = $resolver->resolve($competition, (int) $data['birth_year']);

        if ($ageGroup === null) {
            return back()->withInput()->withErrors([
                'birth_year' => 'Usia atlet di luar rentang kejuaraan ini',
            ]);
        }

        $wizard->put($competition, [
            'registrant' => [
                'name' => $data['registrant_name'],
                'phone' => $data['registrant_phone'],
                'email' => $data['registrant_email'] ?? null,
            ],
            'athlete' => [
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'birth_year' => (int) $data['birth_year'],
                'club_name' => $data['club_name'],
                'club_city' => $data['club_city'],
            ],
            'event_ids' => [],
            'seed_times' => [],
        ]);

        return redirect()->route('register.events', $competition);
    }

    public function events(
        Competition $competition,
        PublicRegistrationWizard $wizard,
        AgeGroupResolver $resolver,
        SeedTimeSuggester $suggester,
    ): View|RedirectResponse {
        $this->assertOpen($competition);

        $state = $wizard->state($competition);
        $athlete = $wizard->athlete($state);

        if (! $athlete instanceof Athlete) {
            return redirect()->route('register.create', $competition);
        }

        $ageGroup = $resolver->resolve($competition, $athlete->birth_year);

        if ($ageGroup === null) {
            return redirect()->route('register.create', $competition)
                ->withErrors(['birth_year' => 'Usia atlet di luar rentang kejuaraan ini']);
        }

        $events = $this->eligibleEvents($competition, $athlete, $ageGroup->id);

        $suggestions = [];
        foreach ($events as $event) {
            $suggestions[$event->id] = $athlete->exists
                ? $suggester->suggest($athlete, $event, $competition)
                : null;
        }

        return view('register.events', [
            'competition' => $competition,
            'athlete' => $athlete,
            'ageGroup' => $ageGroup,
            'events' => $events,
            'state' => $state,
            'suggestions' => $suggestions,
            'used' => $this->quotaUsed($competition, $athlete),
        ]);
    }

    public function storeEvents(
        StoreRegistrationEventsRequest $request,
        Competition $competition,
        PublicRegistrationWizard $wizard,
    ): RedirectResponse {
        $this->assertOpen($competition);

        $state = $wizard->state($competition);

        if (! isset($state['athlete'])) {
            return redirect()->route('register.create', $competition);
        }

        $wizard->put($competition, [
            ...$state,
            'event_ids' => $request->validated('event_ids'),
            'seed_times' => $request->validated('seed_times') ?? [],
        ]);

        return redirect()->route('register.review', $competition);
    }

    public function review(
        Competition $competition,
        PublicRegistrationWizard $wizard,
        AgeGroupResolver $resolver,
    ): View|RedirectResponse {
        $this->assertOpen($competition);

        $state = $wizard->state($competition);
        $athlete = $wizard->athlete($state);
        $eventIds = $state['event_ids'] ?? [];

        if (! $athlete instanceof Athlete || $eventIds === []) {
            return redirect()->route('register.create', $competition);
        }

        $events = Event::query()->whereIn('id', $eventIds)->orderBy('event_number')->get();
        $seedTimes = $state['seed_times'] ?? [];
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
        session(['register_token.'.$competition->id => $token]);

        return view('register.review', [
            'competition' => $competition,
            'athlete' => $athlete,
            'ageGroup' => $resolver->resolve($competition, $athlete->birth_year),
            'events' => $events,
            'state' => $state,
            'parsed' => $parsed,
            'token' => $token,
            'totalFee' => 0,
        ]);
    }

    public function store(
        SubmitRegistrationRequest $request,
        Competition $competition,
        PublicRegistrationWizard $wizard,
        RegistrationValidator $validator,
        SubmitPublicRegistration $submit,
    ): RedirectResponse {
        $this->assertOpen($competition);

        $expected = session('register_token.'.$competition->id);
        $token = $request->validated('submit_token');

        if (! is_string($expected) || $token !== $expected) {
            return back()->withErrors(['submit_token' => 'Pengiriman tidak valid. Ulangi dari ringkasan.']);
        }

        // Menahan klik ganda: token yang sama hanya boleh menembus satu kali.
        $lock = 'public-registration:'.$token;

        if (! Cache::add($lock, true, 600)) {
            return redirect()->route('register.index');
        }

        $state = $wizard->state($competition);
        $athlete = $wizard->athlete($state);
        $eventIds = $state['event_ids'] ?? [];

        if (! $athlete instanceof Athlete || $eventIds === []) {
            Cache::forget($lock);

            return redirect()->route('register.create', $competition);
        }

        $events = Event::query()->with('ageGroups')->whereIn('id', $eventIds)->get();
        $seedTimes = $state['seed_times'] ?? [];

        $drafts = $events->map(function (Event $event) use ($competition, $athlete, $seedTimes): RegistrationDraft {
            $input = $seedTimes[$event->id] ?? $seedTimes[(string) $event->id] ?? null;

            return new RegistrationDraft($competition, $athlete, $event, is_string($input) ? $input : null);
        })->all();

        $messages = collect($validator->validateMany($drafts))
            ->flatten(1)
            ->pluck('message')
            ->unique();

        if ($messages->isNotEmpty()) {
            Cache::forget($lock);

            return back()->withErrors(['events' => $messages->implode(' ')]);
        }

        $submission = $submit->handle($competition, $state, $request->ip());

        $wizard->forget($competition);
        session()->forget('register_token.'.$competition->id);
        session([$this->receiptKey($submission) => true]);

        return redirect()->route('register.done', $submission->code);
    }

    /**
     * Halaman bukti pendaftaran. Pendaftar tidak punya akun, jadi tanda bahwa ia
     * memang baru saja mengirim form disimpan di session miliknya sendiri.
     */
    public function done(RegistrationSubmission $submission): View
    {
        abort_unless(session($this->receiptKey($submission)) === true, 404);

        $submission->load(['competition', 'athlete.club', 'registrations.event']);

        return view('register.done', [
            'submission' => $submission,
            'competition' => $submission->competition,
        ]);
    }

    public function parseTime(Request $request): JsonResponse
    {
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

    private function assertOpen(Competition $competition): void
    {
        abort_unless($competition->isOpenForRegistration(), 403, 'Pendaftaran sudah ditutup');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function eligibleEvents(Competition $competition, Athlete $athlete, int $ageGroupId): \Illuminate\Support\Collection
    {
        return $competition->events()
            ->with('ageGroups')
            ->where('gender', $athlete->gender->eventGender())
            ->where('is_active', true)
            ->orderBy('session')
            ->orderBy('sort_order')
            ->orderBy('event_number')
            ->get()
            ->filter(fn (Event $event): bool => $event->ageGroups->contains('id', $ageGroupId))
            ->values();
    }

    private function quotaUsed(Competition $competition, Athlete $athlete): int
    {
        if (! $athlete->exists) {
            return 0;
        }

        return Registration::query()
            ->where('competition_id', $competition->id)
            ->where('athlete_id', $athlete->id)
            ->get()
            ->filter(fn (Registration $registration): bool => $registration->status->countsTowardQuota())
            ->count();
    }

    private function receiptKey(RegistrationSubmission $submission): string
    {
        return 'register_receipt.'.$submission->id;
    }
}
