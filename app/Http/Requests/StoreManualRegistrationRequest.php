<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->managesMasterData() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', 'exists:athletes,id'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'seed_time' => ['nullable', 'string', 'max:20'],
            'verify_now' => ['sometimes', 'boolean'],
            'age_group_id' => ['nullable', 'integer', 'exists:age_groups,id'],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'age_group_id' => 'kelompok umur',
            'override_reason' => 'alasan naik kelas',
        ];
    }
}
