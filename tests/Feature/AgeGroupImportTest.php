<?php

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

function writeAgeGroupCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'grp').'.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, ['KODE', 'NAMA', 'LABEL CETAK', 'TAHUN LAHIR AWAL', 'TAHUN LAHIR AKHIR', 'URUTAN']);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('shows excel import on the age groups page', function () {
    $competition = Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.age-groups.index', $competition))
        ->assertOk()
        ->assertSee('Import Excel')
        ->assertSee('Unduh template Excel')
        ->assertSee('tahun lahir');
});

it('downloads a seeded age group template when none exist yet', function () {
    $competition = Competition::factory()->create(['start_date' => '2026-10-12']);
    $panitia = User::factory()->panitia()->create();

    $response = $this->actingAs($panitia)->get(route('admin.competitions.age-groups.template', $competition));
    $response->assertSuccessful();

    $path = tempnam(sys_get_temp_dir(), 'grp').'.xlsx';
    $base = $response->baseResponse;

    if (method_exists($base, 'getFile')) {
        copy($base->getFile()->getPathname(), $path);
    } else {
        file_put_contents($path, $response->getContent());
    }

    $spreadsheet = IOFactory::load($path);
    $rows = $spreadsheet->getSheetByName('KELOMPOK UMUR')->toArray();

    expect($spreadsheet->getSheetNames())->toBe(['KELOMPOK UMUR', 'PETUNJUK'])
        ->and($rows[0][0] ?? null)->toBe('KODE')
        ->and((string) ($rows[1][0] ?? ''))->toBe('1')
        ->and($rows[1][1] ?? null)->toBe('Group 1')
        ->and((string) ($rows[1][3] ?? ''))->toBe('1950')
        ->and((string) ($rows[1][4] ?? ''))->toBe('2011')
        ->and($rows[9][1] ?? null)->toBe('Group 9');
});

it('imports custom-named age groups from excel', function () {
    $competition = Competition::factory()->create(['start_date' => '2026-10-12']);
    $panitia = User::factory()->panitia()->create();
    $path = writeAgeGroupCsv([
        ['1', 'Searia1', 'I', '2019', '2026', '1'],
        ['2', 'Searia2', 'II', '2017', '2018', '2'],
        ['3', 'Searia3', 'III', '2015', '2016', '3'],
    ]);

    $this->actingAs($panitia)
        ->from(route('admin.competitions.age-groups.index', $competition))
        ->post(route('admin.competitions.age-groups.import', $competition), [
            'file' => new UploadedFile($path, 'grup.csv', 'text/csv', null, true),
        ])
        ->assertRedirect(route('admin.competitions.age-groups.index', $competition))
        ->assertSessionHas('status');

    $groups = $competition->ageGroups()->orderBy('code')->get();

    expect($groups)->toHaveCount(3)
        ->and($groups->pluck('name')->all())->toEqual(['Searia1', 'Searia2', 'Searia3'])
        ->and($groups[0]->birth_year_start)->toBe(2019)
        ->and($groups[0]->birth_year_end)->toBe(2026)
        ->and($groups[0]->display_code)->toBe('I');
});

it('updates an existing group without registrations', function () {
    $competition = Competition::factory()->create();
    $group = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '1',
        'name' => 'Group 1',
        'display_code' => 'I',
        'birth_year_start' => 2019,
        'birth_year_end' => 2026,
        'sort_order' => 1,
    ]);
    $panitia = User::factory()->panitia()->create();
    $path = writeAgeGroupCsv([
        ['1', 'Searia1', 'I', '2019', '2026', '1'],
    ]);

    $this->actingAs($panitia)
        ->post(route('admin.competitions.age-groups.import', $competition), [
            'file' => new UploadedFile($path, 'grup.csv', 'text/csv', null, true),
        ])
        ->assertRedirect();

    expect($group->fresh()->name)->toBe('Searia1')
        ->and($competition->ageGroups()->count())->toBe(1);
});

it('rejects overlapping birth years', function () {
    $competition = Competition::factory()->create();
    $panitia = User::factory()->panitia()->create();
    $path = writeAgeGroupCsv([
        ['1', 'Searia1', 'I', '2018', '2026', '1'],
        ['2', 'Searia2', 'II', '2017', '2018', '2'],
    ]);

    $this->actingAs($panitia)
        ->from(route('admin.competitions.age-groups.index', $competition))
        ->post(route('admin.competitions.age-groups.import', $competition), [
            'file' => new UploadedFile($path, 'grup.csv', 'text/csv', null, true),
        ])
        ->assertRedirect(route('admin.competitions.age-groups.index', $competition))
        ->assertSessionHasErrors('file');

    expect($competition->ageGroups()->count())->toBe(0);
});

it('does not change birth years when the group already has registrations', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);
    $path = writeAgeGroupCsv([
        ['3', 'Group 3', 'III', '2010', '2011', '3'],
    ]);

    $this->actingAs($meet['panitia'])
        ->from(route('admin.competitions.age-groups.index', $meet['competition']))
        ->post(route('admin.competitions.age-groups.import', $meet['competition']), [
            'file' => new UploadedFile($path, 'grup.csv', 'text/csv', null, true),
        ])
        ->assertRedirect(route('admin.competitions.age-groups.index', $meet['competition']))
        ->assertSessionHasErrors('file');

    expect($meet['group']->fresh()->birth_year_start)->toBe(2015)
        ->and($meet['group']->fresh()->birth_year_end)->toBe(2016);
});
