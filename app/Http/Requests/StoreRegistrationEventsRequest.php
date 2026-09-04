<?php

namespace App\Http\Requests;

use App\Models\Competition;
use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition
            && ($this->user()?->can('create', Registration::class) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'event_ids' => ['required', 'array', 'min:1'],
            'event_ids.*' => ['integer', Rule::exists('events', 'id')->where('competition_id', $competition->id)],
            'seed_times' => ['nullable', 'array'],
            'seed_times.*' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'event_ids' => 'nomor lomba',
        ];
    }
}
