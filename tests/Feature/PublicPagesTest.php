<?php

use App\Enums\CompetitionStatus;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    SitePage::factory()->create([
        'slug' => 'about',
        'title' => 'Pengenalan',
        'body' => 'Deskripsi penyelenggara dan kategori lomba.',
    ]);
    SitePage::factory()->create([
        'slug' => 'terms',
        'title' => 'Syarat dan ketentuan',
        'body' => 'Naskah lengkap termasuk kebijakan privasi.',
    ]);
});

it('keeps draft competitions off the public home page', function () {
    Competition::factory()->status(CompetitionStatus::Draft)->create(['name' => 'DRAFT MEET HIDDEN']);
    Competition::factory()->status(CompetitionStatus::Registration)->create(['name' => 'OPEN MEET VISIBLE']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('OPEN MEET VISIBLE')
        ->assertDontSee('DRAFT MEET HIDDEN');
});

it('returns 200 on home when there are no open competitions', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('tidak ada kejuaraan');
});

it('serves about and terms pages', function () {
    $this->get(route('about'))->assertOk()->assertSee('Pengenalan');
    $this->get(route('terms'))->assertOk()->assertSee('kebijakan privasi');
});

it('shows schedule milestones for a non-draft competition', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create([
        'name' => 'Meet Jadwal',
        'registration_opens_at' => now()->subWeek(),
        'registration_closes_at' => now()->addWeek(),
        'technical_meeting_at' => now()->addDays(10),
        'start_date' => now()->addDays(12)->toDateString(),
        'end_date' => now()->addDays(13)->toDateString(),
    ]);

    $this->get(route('public.competitions.schedule', $competition))
        ->assertOk()
        ->assertSee('Masa pendaftaran')
        ->assertSee('Technical meeting')
        ->assertSee('Hari lomba');
});

it('shows fee unavailable copy when fee is zero', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create([
        'fee_per_event' => 0,
        'late_fee_per_event' => 0,
    ]);

    $this->get(route('public.competitions.fees', $competition))
        ->assertOk()
        ->assertSee('Harga belum tersedia');
});

it('lists only published competitions in the archive', function () {
    Competition::factory()->status(CompetitionStatus::Finished)->create(['name' => 'FINISHED ONLY']);
    Competition::factory()->status(CompetitionStatus::Published)->create([
        'name' => 'PUBLISHED MEET',
        'published_at' => now(),
    ]);

    $this->get(route('archive.index'))
        ->assertOk()
        ->assertSee('PUBLISHED MEET')
        ->assertDontSee('FINISHED ONLY');
});

it('never exposes identity number or birth date on public athlete pages', function () {
    $meet = openRegistrationMeet();
    $athlete = $meet['athlete'];
    $athlete->update([
        'identity_number' => 'SECRET-ID-999',
        'birth_date' => '2016-05-20',
        'full_name' => 'PUBLIC SEARCH ATHLETE',
    ]);
    $meet['competition']->update([
        'status' => CompetitionStatus::Published,
        'published_at' => now(),
    ]);
    verifiedRegistration($meet);

    $this->get(route('public.athletes.search', ['q' => 'PUBLIC SEARCH']))
        ->assertOk()
        ->assertSee('PUBLIC SEARCH ATHLETE')
        ->assertDontSee('SECRET-ID-999')
        ->assertDontSee('2016-05-20');

    $this->get(route('public.athletes.show', $athlete))
        ->assertOk()
        ->assertSee('PUBLIC SEARCH ATHLETE')
        ->assertSee((string) $athlete->birth_year)
        ->assertDontSee('SECRET-ID-999')
        ->assertDontSee('2016-05-20')
        ->assertDontSee('identity_number')
        ->assertDontSee('birth_date');
});

it('hides draft competitions from schedule and fees', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Draft)->create();

    $this->get(route('public.competitions.schedule', $competition))->assertNotFound();
    $this->get(route('public.competitions.fees', $competition))->assertNotFound();
});

it('serves a sitemap xml document', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('content-type', 'application/xml')
        ->assertSee(route('archive.index'), false);
});

it('shows a countdown and three recent published meets on the home page', function () {
    Competition::factory()->status(CompetitionStatus::Registration)->create([
        'name' => 'OPEN COUNTDOWN MEET',
        'registration_closes_at' => now()->addDays(2),
    ]);

    foreach (['RECENT ONE', 'RECENT TWO', 'RECENT THREE', 'RECENT FOUR'] as $index => $name) {
        Competition::factory()->status(CompetitionStatus::Published)->create([
            'name' => $name,
            'published_at' => now()->subDays($index),
        ]);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('OPEN COUNTDOWN MEET')
        ->assertSee('data-countdown', false)
        ->assertSee('RECENT ONE')
        ->assertSee('RECENT TWO')
        ->assertSee('RECENT THREE')
        ->assertDontSee('RECENT FOUR');
});

it('filters the archive by year', function () {
    Competition::factory()->status(CompetitionStatus::Published)->create([
        'name' => 'MEET 2024',
        'start_date' => '2024-06-01',
        'end_date' => '2024-06-02',
        'published_at' => now(),
    ]);
    Competition::factory()->status(CompetitionStatus::Published)->create([
        'name' => 'MEET 2025',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-02',
        'published_at' => now(),
    ]);

    $this->get(route('archive.index', ['year' => 2024]))
        ->assertOk()
        ->assertSee('MEET 2024')
        ->assertDontSee('MEET 2025');
});

it('formats schedule dates in Indonesian', function () {
    \Carbon\Carbon::setLocale('id');
    app()->setLocale('id');

    $competition = Competition::factory()->status(CompetitionStatus::Registration)->create([
        'registration_opens_at' => '2026-03-01 09:00:00',
        'registration_closes_at' => '2026-03-10 17:00:00',
        'technical_meeting_at' => '2026-03-12 10:00:00',
        'start_date' => '2026-03-15',
        'end_date' => '2026-03-15',
    ]);

    $this->get(route('public.competitions.schedule', $competition))
        ->assertOk()
        ->assertSee('Maret');
});

it('embeds open graph tags on public result pages', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Published)->create([
        'name' => 'OG RESULTS MEET',
        'published_at' => now(),
    ]);

    $this->get(route('results.index', $competition))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('OG RESULTS MEET');
});

it('rate limits athlete search requests', function () {
    \Illuminate\Support\Facades\RateLimiter::clear(md5('athlete-search'.'|127.0.0.1'));

    for ($i = 0; $i < 30; $i++) {
        $this->get(route('public.athletes.search', ['q' => 'ab']))->assertOk();
    }

    $this->get(route('public.athletes.search', ['q' => 'ab']))->assertStatus(429);
});
