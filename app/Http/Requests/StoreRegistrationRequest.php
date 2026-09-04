<?php

namespace App\Http\Requests;

use App\Models\Competition;
use App\Models\Registration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition
            && ($this->user()?->can('create', Registration::class) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'submit_token' => ['required', 'string'],
            'athlete_id' => ['sometimes', 'integer'],
            'event_ids' => ['sometimes', 'array'],
            'seed_times' => ['sometimes', 'array'],
        ];
    }
}
