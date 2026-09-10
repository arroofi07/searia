<?php

namespace Database\Seeders;

use App\Actions\FillDefaultProgram;
use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\Gender;
use App\Enums\SeedingMode;
use App\Enums\UserRole;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Competition;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SitePage::query()->updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'Pengenalan SeaRIA',
                'body' => "SeaRIA adalah sistem informasi kejuaraan renang untuk penyelenggara, pelatih, juri, dan peserta.\n\nKategori lomba mencakup nomor perorangan berbagai gaya dan jarak, dikelompokkan menurut kelompok umur. Jenis kejuaraan dapat Resmi atau Fun sesuai keputusan panitia.\n\nHalaman ini dapat disunting dari dasbor panitia.",
            ],
        );

        SitePage::query()->updateOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Syarat, ketentuan, dan kebijakan privasi',
                'body' => "Dengan menggunakan SeaRIA, Anda menyetujui ketentuan berikut.\n\n1. Data peserta dikelola untuk keperluan kejuaraan dan tidak dipublikasikan melebihi kebutuhan (nama, klub, tahun lahir, hasil).\n2. Nomor identitas dan tanggal lahir lengkap tidak ditampilkan di halaman publik.\n3. Hasil resmi baru berlaku setelah kejuaraan berstatus dipublikasikan.\n4. Panitia dapat mengkoreksi hasil dengan jejak audit.\n5. Kebijakan privasi: data pribadi hanya diakses oleh peran yang berwenang (panitia dan juri pada penugasan).\n\nNaskah ini dapat diperbarui oleh panitia tanpa mengubah kode aplikasi.",
            ],
        );

        $clubA = Club::query()->create([
            'name' => 'SeaRIA Aquatic Padang',
            'short_name' => 'SAP',
            'type' => ClubType::Perkumpulan,
            'city' => 'Padang',
            'province' => 'Sumatera Barat',
            'contact_name' => 'Official SeaRIA',
            'contact_phone' => '081234567890',
            'status' => ClubStatus::Verified,
            'is_active' => true,
        ]);

        Club::query()->create([
            'name' => 'Gunung Sport Center',
            'short_name' => 'GSC',
            'type' => ClubType::Perkumpulan,
            'city' => 'Padang',
            'province' => 'Sumatera Barat',
            'contact_name' => 'Official GSC',
            'contact_phone' => '081298765432',
            'status' => ClubStatus::Pending,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Super Admin',
            'email' => 'admin@searia.test',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Panitia',
            'email' => 'panitia@searia.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Panitia,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Juri Kolam 1',
            'email' => 'juri@searia.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Juri,
            'is_active' => true,
        ]);

        Athlete::query()->create([
            'club_id' => $clubA->id,
            'full_name' => 'AHZA DANISH RAHMAN',
            'gender' => Gender::Male,
            'birth_year' => 2016,
            'is_active' => true,
        ]);

        Athlete::query()->create([
            'club_id' => $clubA->id,
            'full_name' => 'MUTYA ZAHIRA TANJUNG',
            'gender' => Gender::Female,
            'birth_year' => 2017,
            'is_active' => true,
        ]);

        $competition = Competition::query()->create([
            'name' => 'SeaRIA Aquatic Championship 2026',
            'venue' => 'Kolam Renang Painan',
            'city' => 'Pesisir Selatan',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-13',
            'registration_opens_at' => '2026-09-01 08:00:00',
            'registration_closes_at' => '2026-10-10 23:59:00',
            'technical_meeting_at' => '2026-10-11 19:00:00',
            'type' => CompetitionType::Official,
            'pool_lanes' => 6,
            'pool_length' => 25,
            'max_events_per_athlete' => 3,
            'seeding_mode' => SeedingMode::Balanced,
            'fee_per_event' => 0,
            'late_fee_per_event' => 0,
            'status' => CompetitionStatus::Registration,
        ]);

        foreach (AgeGroup::defaultDefinitions(2026) as $definition) {
            $competition->ageGroups()->create($definition);
        }

        app(FillDefaultProgram::class)->handle($competition);
    }
}
