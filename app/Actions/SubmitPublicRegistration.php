<?php

namespace App\Actions;

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Services\AgeGroupResolver;
use App\Support\SwimTime;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan satu pengiriman form pendaftaran publik. Klub, atlet, pengiriman,
 * dan seluruh entri lahir dalam satu transaksi agar tidak ada pendaftaran
 * setengah jadi ketika salah satu langkah gagal. MVP tidak menerbitkan tagihan.
 */
class SubmitPublicRegistration
{
    public function __construct(
        private readonly AgeGroupResolver $ageGroups,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     */
    public function handle(Competition $competition, array $state, ?string $ipAddress = null): RegistrationSubmission
    {
        return DB::transaction(function () use ($competition, $state, $ipAddress): RegistrationSubmission {
            $club = $this->club($state);
            $athlete = $this->athlete($state, $club);
            $ageGroup = $this->ageGroups->resolve($competition, $athlete->birth_year);

            $submission = RegistrationSubmission::query()->create([
                'competition_id' => $competition->id,
                'athlete_id' => $athlete->id,
                'code' => RegistrationSubmission::generateCode(),
                'registrant_name' => $state['registrant']['name'],
                'registrant_phone' => $state['registrant']['phone'],
                'registrant_email' => $state['registrant']['email'] ?? null,
                'ip_address' => $ipAddress,
            ]);

            $seedTimes = $state['seed_times'] ?? [];

            foreach ($state['event_ids'] as $eventId) {
                $input = $seedTimes[$eventId] ?? $seedTimes[(string) $eventId] ?? null;

                Registration::query()->create([
                    'competition_id' => $competition->id,
                    'event_id' => $eventId,
                    'athlete_id' => $athlete->id,
                    'submission_id' => $submission->id,
                    'age_group_id' => $ageGroup?->id,
                    'seed_time_ms' => SwimTime::parse(is_string($input) ? $input : null)?->milliseconds,
                    'status' => RegistrationStatus::Pending,
                    'registered_by' => null,
                ]);
            }

            return $submission->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function club(array $state): Club
    {
        $data = $state['athlete'];

        // Peserta mengetik nama klub sendiri. Nama yang sama dipakai ulang;
        // klub baru masuk sebagai `pending` sampai panitia verifikasi.
        return Club::query()->firstOrCreate(
            ['name' => $data['club_name']],
            [
                'type' => ClubType::Perkumpulan,
                'city' => $data['club_city'],
                'contact_name' => $state['registrant']['name'],
                'contact_phone' => $state['registrant']['phone'],
                'status' => ClubStatus::Pending,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function athlete(array $state, Club $club): Athlete
    {
        $data = $state['athlete'];

        return Athlete::query()->firstOrCreate(
            [
                'club_id' => $club->id,
                'full_name' => $data['full_name'],
                'birth_year' => (int) $data['birth_year'],
            ],
            [
                'gender' => Gender::from($data['gender']),
                'is_active' => true,
            ],
        );
    }
}
