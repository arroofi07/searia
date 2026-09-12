<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

function writeEventProgramCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'evt').'.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, ['KODE ACARA', 'NOMOR PERLOMBAAN', 'GENDER', 'GRUP YANG BOLEH IKUT']);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('shows excel import on the events page', function () {
    $competition = Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.events.index', $competition))
        ->assertOk()
        ->assertSee('Import Excel')
        ->assertSee('Unduh template Excel')
        ->assertSee('GRUP YANG BOLEH IKUT');
});

it('downloads an unlocked event program template', function () {
    $competition = Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();

    $response = $this->actingAs($panitia)->get(route('admin.competitions.events.template', $competition));
    $response->assertSuccessful();

    $path = tempnam(sys_get_temp_dir(), 'prg').'.xlsx';
    $base = $response->baseResponse;

    if (method_exists($base, 'getFile')) {
        copy($base->getFile()->getPathname(), $path);
    } else {
        file_put_contents($path, $response->getContent());
    }

    $spreadsheet = IOFactory::load($path);

    expect($spreadsheet->getSheetNames())->toBe(['NOMOR LOMBA', 'PETUNJUK'])
        ->and($spreadsheet->getSheetByName('NOMOR LOMBA')->getProtection()->getSheet())->not->toBeTrue();
});

it('imports events and eligible groups from excel', function () {
    $competition = Competition::factory()->create();
    $group1 = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '1',
        'name' => 'Group 1',
        'sort_order' => 1,
    ]);
    $group2 = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '2',
        'name' => 'Group 2',
        'sort_order' => 2,
    ]);
    $panitia = User::factory()->panitia()->create();
    $path = writeEventProgramCsv([
        ['1', '50 M Gaya Kupu-Kupu', 'Putra', 'Group 1, Group 2'],
        ['2', '50 M Gaya Kupu-Kupu', 'Putri', 'Group 1'],
    ]);

    $this->actingAs($panitia)
        ->from(route('admin.competitions.events.index', $competition))
        ->post(route('admin.competitions.events.import', $competition), [
            'file' => new UploadedFile($path, 'nomor.csv', 'text/csv', null, true),
        ])
        ->assertRedirect(route('admin.competitions.events.index', $competition))
        ->assertSessionHas('status');

    $putra = $competition->events()->where('event_number', 1)->first();
    $putri = $competition->events()->where('event_number', 2)->first();

    expect($competition->events()->count())->toBe(2)
        ->and($putra?->stroke)->toBe(Stroke::Butterfly)
        ->and($putra?->gender)->toBe(EventGender::Male)
        ->and($putra?->equipment)->toBe(Equipment::None)
        ->and($putra?->ageGroups()->pluck('age_groups.id')->all())->toEqualCanonicalizing([$group1->id, $group2->id])
        ->and($putri?->gender)->toBe(EventGender::Female)
        ->and($putri?->ageGroups()->pluck('age_groups.id')->all())->toEqualCanonicalizing([$group1->id]);
});

it('updates groups on an existing event without registrations', function () {
    $competition = Competition::factory()->create();
    $group1 = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '1',
        'name' => 'Group 1',
    ]);
    $group3 = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'name' => 'Group 3',
    ]);
    $event = Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 13,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => Stroke::Breaststroke,
        'equipment' => Equipment::None,
    ]);
    $event->ageGroups()->attach($group1->id);
    $panitia = User::factory()->panitia()->create();
    $path = writeEventProgramCsv([
        ['13', '50 M Gaya Dada', 'Putra', 'Group 3'],
    ]);

    $this->actingAs($panitia)
        ->post(route('admin.competitions.events.import', $competition), [
            'file' => new UploadedFile($path, 'nomor.csv', 'text/csv', null, true),
        ])
        ->assertRedirect();

    expect($event->fresh()->ageGroups()->pluck('age_groups.id')->all())->toEqualCanonicalizing([$group3->id]);
});

it('does not change an event that already has registrations', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $path = writeEventProgramCsv([
        ['13', '50 M Gaya Bebas', 'Putra', $meet['group']->name],
    ]);

    $this->actingAs($meet['panitia'])
        ->from(route('admin.competitions.events.index', $meet['competition']))
        ->post(route('admin.competitions.events.import', $meet['competition']), [
            'file' => new UploadedFile($path, 'nomor.csv', 'text/csv', null, true),
        ])
        ->assertRedirect(route('admin.competitions.events.index', $meet['competition']))
        ->assertSessionHasErrors('file');

    expect($meet['event']->fresh()->stroke)->toBe(Stroke::Breaststroke);
});
