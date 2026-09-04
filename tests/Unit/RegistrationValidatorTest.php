<?php

use App\Enums\ClubStatus;
use App\Enums\CompetitionStatus;
use App\Enums\EventGender;
use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Services\AgeGroupResolver;
use App\Services\RegistrationDraft;
use App\Services\RegistrationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function draftsFrom(array $meet, Event $event, ?string $time = '00:52.20'): RegistrationDraft
{
    return new RegistrationDraft($meet['competition'], $meet['athlete'], $event->load('ageGroups'), $time);
}

it('reports V-01 when the competition is not open for registration', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $meet['event']), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-01')
        ->and(collect($errors)->firstWhere('code', 'V-01')['message'])->toBe('Pendaftaran sudah ditutup');
});

it('reports V-02 when the birth year matches no age group', function () {
    $meet = openRegistrationMeet();
    $meet['athlete']->update(['birth_year' => 2010]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $meet['event']), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-02');
});

it('reports V-03 when the age group is not eligible for the event', function () {
    $meet = openRegistrationMeet();
    $other = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 5,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => \App\Enums\Stroke::Butterfly,
        'equipment' => \App\Enums\Equipment::Fins,
    ]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $other), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-03')
        ->and(collect($errors)->firstWhere('code', 'V-03')['message'])->toContain('Group 3 tidak mengikuti nomor');
});

it('reports V-04 when the athlete gender does not match the event', function () {
    $meet = openRegistrationMeet();
    $pi = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'gender' => EventGender::Female,
    ]);
    $pi->ageGroups()->attach($meet['group']->id);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $pi), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-04')
        ->and(collect($errors)->firstWhere('code', 'V-04')['message'])->toBe('Nomor ini khusus putri');
});

it('reports V-05 when the athlete is already entered in the event', function () {
    $meet = openRegistrationMeet();
    Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['coach']->id,
        'status' => RegistrationStatus::Pending,
    ]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $meet['event']), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-05');
});

it('reports V-06 including events that are still in the current batch', function () {
    $meet = openRegistrationMeet();
    $events = collect([$meet['event']]);
    foreach ([15, 17, 19] as $number) {
        $event = Event::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_number' => $number,
            'gender' => EventGender::Male,
        ]);
        $event->ageGroups()->attach($meet['group']->id);
        $events->push($event);
    }

    $batch = $events->map(fn (Event $event) => draftsFrom($meet, $event))->all();
    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate($batch[0], $batch, $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-06')
        ->and(collect($errors)->firstWhere('code', 'V-06')['message'])->toBe('Maksimal 3 nomor per atlet');
});

it('reports V-07 for an unparseable seed time', function () {
    $meet = openRegistrationMeet();
    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(
        draftsFrom($meet, $meet['event'], 'bukan-waktu'),
        [],
        $meet['coach'],
    );

    expect(collect($errors)->pluck('code')->all())->toContain('V-07');
});

it('reports V-08 when the seed time is unrealistically fast', function () {
    $meet = openRegistrationMeet();
    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(
        draftsFrom($meet, $meet['event'], '00:05.20'),
        [],
        $meet['coach'],
    );

    expect(collect($errors)->pluck('code')->all())->toContain('V-08')
        ->and(collect($errors)->firstWhere('code', 'V-08')['message'])->toBe('Waktu terlalu cepat untuk jarak 50 m');
});

it('reports V-09 when the coach club is not verified', function () {
    $meet = openRegistrationMeet();
    $meet['club']->update(['status' => ClubStatus::Pending]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(draftsFrom($meet, $meet['event']), [], $meet['coach']);

    expect(collect($errors)->pluck('code')->all())->toContain('V-09');
});

it('reports all three violations together instead of stopping at the first', function () {
    $meet = openRegistrationMeet();
    $meet['athlete']->update(['birth_year' => 2010, 'gender' => Gender::Male]);
    $pi = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 14,
        'gender' => EventGender::Female,
    ]);

    $errors = (new RegistrationValidator(new AgeGroupResolver))->validate(
        draftsFrom($meet, $pi, 'xyz'),
        [],
        $meet['coach'],
    );

    expect(collect($errors)->pluck('code')->all())->toContain('V-02', 'V-04', 'V-07');
});
