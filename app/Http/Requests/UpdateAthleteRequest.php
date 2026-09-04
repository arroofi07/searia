<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Athlete;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $athlete instanceof Athlete && ($this->user()?->can('update', $athlete) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $athlete = $this->route('athlete');

        if ($athlete instanceof Athlete && $this->user()?->cannot('manageAny', Athlete::class)) {
            $this->merge([
                'club_id' => $athlete->club_id,
            ]);
        }
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'full_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_year' => ['required', 'integer', 'min:1950', 'max:'.$currentYear],
            'birth_date' => ['nullable', 'date'],
            'identity_number' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'club_id' => 'klub',
            'full_name' => 'nama lengkap',
            'gender' => 'jenis kelamin',
            'birth_year' => 'tahun lahir',
            'birth_date' => 'tanggal lahir',
            'identity_number' => 'nomor identitas',
            'photo' => 'foto',
            'is_active' => 'status aktif',
        ];
    }
}
