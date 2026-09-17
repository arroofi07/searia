<?php

namespace App\Http\Requests\Public;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Langkah 1 form publik: identitas peserta sesuai kolom Excel
 * (nama lengkap, L/P, tahun lahir, klub/sekolah, kabupaten/kota).
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
            'full_name' => 'nama lengkap',
            'gender' => 'L/P',
            'birth_year' => 'tahun lahir',
            'club_name' => 'klub/sekolah',
            'club_city' => 'kabupaten/kota',
        ];
    }
}
