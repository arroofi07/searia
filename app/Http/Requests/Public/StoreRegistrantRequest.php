<?php

namespace App\Http\Requests\Public;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Langkah 1 form publik: kontak pendaftar dan identitas atlet.
 * Klub selalu diketik sendiri oleh pendaftar (nama + kabupaten/kota).
 */
class StoreRegistrantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'registrant_name' => ['required', 'string', 'min:3', 'max:100'],
            'registrant_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'registrant_email' => ['nullable', 'email', 'max:120'],

            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_year' => ['required', 'integer', 'min:1950', 'max:'.$currentYear],

            'club_name' => ['required', 'string', 'min:3', 'max:100'],
            'club_city' => ['required', 'string', 'max:100'],

            // Umpan untuk bot: kolom ini disembunyikan dengan CSS dan harus tetap kosong.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'registrant_name' => 'nama pendaftar',
            'registrant_phone' => 'nomor WhatsApp',
            'registrant_email' => 'email',
            'full_name' => 'nama lengkap atlet',
            'gender' => 'jenis kelamin',
            'birth_year' => 'tahun lahir',
            'club_name' => 'nama klub atau sekolah',
            'club_city' => 'kabupaten atau kota',
        ];
    }
}
