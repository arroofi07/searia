<?php

namespace App\Http\Requests;

use App\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImportRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('importBatch');

        return $batch instanceof ImportBatch
            && ($this->user()?->can('update', $batch) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'string', 'max:5'],
            'birth_year' => ['required', 'string', 'max:8'],
            'club_name' => ['required', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'event_code' => ['required', 'string', 'max:10'],
            'seed_time' => ['nullable', 'string', 'max:20'],
            'mapped_club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'mapped_athlete_id' => ['nullable', 'integer', 'exists:athletes,id'],
        ];
    }
}
