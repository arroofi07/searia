<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAthleteRegistrationRequest extends FormRequest
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
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'event_ids' => ['required', 'array', 'min:1'],
            'event_ids.*' => ['integer', 'distinct', 'exists:events,id'],
            'seed_times' => ['nullable', 'array'],
            'seed_times.*' => ['nullable', 'string', 'max:20'],
            'verify_now' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'competition_id' => 'kejuaraan',
            'event_ids' => 'nomor lomba',
            'event_ids.*' => 'nomor lomba',
        ];
    }
}
