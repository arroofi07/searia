<?php

namespace Database\Seeders;

use App\Actions\CommitImportBatch;
use App\Actions\FillDefaultProgram;
use App\Enums\ClubType;
use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\ImportStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SeedingMode;
use App\Enums\UserRole;
use App\Models\AgeGroup;
use App\Models\Club;
use App\Models\Competition;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Import\ParticipantFileReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class FunSwimmingSeries1Seeder extends Seeder
{
    public function run(): void
    {
        $source = $this->sourcePath();
        $user = User::query()->where('role', UserRole::Panitia)->first()
            ?? User::query()->where('role', UserRole::SuperAdmin)->firstOrFail();

        $competition = Competition::query()->firstOrCreate(
            ['slug' => 'fun-swimming-searia-series-1'],
            [
                'name' => 'FUN SWIMMING SeaRIA SERIES 1',
                'venue' => 'Kolam Renang Painan',
                'city' => 'Pesisir Selatan',
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-13',
                'registration_opens_at' => '2026-09-01 08:00:00',
                'registration_closes_at' => '2026-10-10 23:59:00',
                'technical_meeting_at' => '2026-10-11 19:00:00',
                'type' => CompetitionType::Fun,
                'pool_lanes' => 6,
                'pool_length' => 25,
                'max_events_per_athlete' => 15,
                'seeding_mode' => SeedingMode::Balanced,
                'fee_per_event' => 0,
                'late_fee_per_event' => 0,
                'status' => CompetitionStatus::Registration,
                'description' => 'Data diimpor dari berkas nomor lomba aplikasi.',
            ],
        );

        if ($competition->max_events_per_athlete < 15) {
            $competition->update(['max_events_per_athlete' => 15]);
        }

        if ($competition->status !== CompetitionStatus::Registration) {
            $competition->update(['status' => CompetitionStatus::Registration]);
        }

        foreach (AgeGroup::defaultDefinitions($competition->year()) as $definition) {
            $competition->ageGroups()->firstOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }

        app(FillDefaultProgram::class)->handle($competition->fresh());

        if ($competition->registrations()->exists()) {
            $this->command?->info('Peserta sudah ada, impor dilewati.');

            return;
        }

        $cleanedPath = $this->writeCleanedWorkbook($source);
        $result = app(ParticipantFileReader::class)->readAndValidate($cleanedPath, 'xlsx', $competition->fresh());

        if ($result->invalid > 0) {
            $messages = [];

            foreach ($result->invalidRows() as $row) {
                $messages[] = 'Baris '.$row->row->excelRow.' '.$row->row->fullName.': '.collect($row->errors)->pluck('message')->implode('; ');
            }

            throw new RuntimeException("Impor peserta gagal:\n".implode("\n", $messages));
        }

        $storedPath = 'imports/'.$competition->id.'/nomor-lomba-aplikasi.xlsx';
        Storage::disk('local')->put($storedPath, (string) file_get_contents($cleanedPath));

        $batch = ImportBatch::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'original_filename' => 'nomor-lomba aplikasi.xlsx',
            'stored_path' => $storedPath,
            'status' => ImportStatus::Validated,
        ]);
        $batch->storeResult($result);

        app(CommitImportBatch::class)->handle($batch);

        $competition->registrations()->update(['status' => RegistrationStatus::Verified]);
        $this->normalizeClubs();

        $this->command?->info(
            'Kejuaraan #'.$competition->id.' siap: '
            .$competition->events()->count().' nomor, '
            .$competition->ageGroups()->count().' grup, '
            .$competition->registrations()->count().' pendaftaran.',
        );
    }

    private function sourcePath(): string
    {
        $candidates = [
            storage_path('app/imports/nomor-lomba-aplikasi.xlsx'),
            'C:\\Users\\RYZEN 5\\Downloads\\nomor-lomba aplikasi.xlsx',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Berkas nomor-lomba aplikasi.xlsx tidak ditemukan.');
    }

    private function writeCleanedWorkbook(string $source): string
    {
        $spreadsheet = IOFactory::load($source);
        $sheet = $spreadsheet->getSheetByName('PESERTA');

        if ($sheet === null) {
            throw new RuntimeException('Lembar PESERTA tidak ditemukan.');
        }

        foreach ($sheet->getRowIterator(2) as $row) {
            $index = $row->getRowIndex();
            $time = $this->excelTimeToSeed($sheet->getCell('H'.$index)->getValue());

            if ($time !== '') {
                $sheet->getCell('H'.$index)->setValueExplicit($time, DataType::TYPE_STRING);
            }
        }

        $path = storage_path('app/imports/nomor-lomba-aplikasi-cleaned.xlsx');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function excelTimeToSeed(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 1) {
            $totalSeconds = (int) round(((float) $value) * 86_400);
            $minutes = intdiv($totalSeconds, 60);
            $seconds = $totalSeconds % 60;

            if ($seconds === 0 && $minutes > 0 && $minutes < 100) {
                return sprintf('00:%02d.00', $minutes);
            }

            return sprintf('%02d:%02d.00', $minutes, $seconds);
        }

        return trim((string) $value);
    }

    private function normalizeClubs(): void
    {
        foreach (Club::query()->get() as $club) {
            $city = trim($club->city);
            $upper = mb_strtoupper($city);

            $club->update([
                'city' => $upper === 'PADANG' ? 'Padang' : $city,
                'province' => 'Sumatera Barat',
                'type' => $this->clubType($club->name),
            ]);
        }
    }

    private function clubType(string $name): ClubType
    {
        $normalized = mb_strtoupper($name);

        if (preg_match('/\b(SD|SDN|SDI|SDIT|SMP|SMPN|MI|MIN|TK|MTS|SMA|SMAN)\b/u', $normalized) === 1) {
            return ClubType::Sekolah;
        }

        return ClubType::Perkumpulan;
    }
}
