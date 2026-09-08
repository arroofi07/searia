<?php

namespace App\Services\Registration;

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;

/**
 * Menyimpan isian tiga langkah form pendaftaran publik di session. Tidak ada baris
 * yang ditulis ke basis data sampai langkah terakhir, sehingga wizard yang
 * ditinggalkan tidak meninggalkan atlet atau klub yatim.
 */
class PublicRegistrationWizard
{
    /**
     * @return array<string, mixed>
     */
    public function state(Competition $competition): array
    {
        $state = session($this->key($competition), []);

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function put(Competition $competition, array $data): void
    {
        session([$this->key($competition) => $data]);
    }

    public function forget(Competition $competition): void
    {
        session()->forget($this->key($competition));
    }

    public function hasRegistrant(Competition $competition): bool
    {
        return is_array($this->state($competition)['athlete'] ?? null);
    }

    /**
     * Atlet yang namanya sudah pernah tercatat di klub yang sama dipakai ulang agar
     * riwayat catatan waktunya ikut terbawa. Selebihnya dikembalikan sebagai model
     * yang belum tersimpan supaya langkah 2 dan 3 tetap bisa memvalidasi.
     *
     * @param  array<string, mixed>  $state
     */
    public function athlete(array $state): ?Athlete
    {
        $data = $state['athlete'] ?? null;

        if (! is_array($data) || ($data['full_name'] ?? '') === '') {
            return null;
        }

        $clubName = trim((string) ($data['club_name'] ?? ''));
        $club = $clubName !== ''
            ? Club::query()->where('name', $clubName)->first()
            : null;

        if ($club !== null) {
            $existing = Athlete::query()
                ->with('club')
                ->where('club_id', $club->id)
                ->where('full_name', $data['full_name'])
                ->where('birth_year', $data['birth_year'])
                ->first();

            if ($existing instanceof Athlete) {
                return $existing;
            }
        }

        $athlete = new Athlete([
            'club_id' => $club?->id,
            'full_name' => $data['full_name'],
            'gender' => Gender::from($data['gender']),
            'birth_year' => (int) $data['birth_year'],
            'is_active' => true,
        ]);

        $athlete->setRelation('club', $club ?? new Club([
            'name' => $clubName,
            'city' => $data['club_city'] ?? '',
        ]));

        return $athlete;
    }

    private function key(Competition $competition): string
    {
        return 'public_registration.'.$competition->id;
    }
}
