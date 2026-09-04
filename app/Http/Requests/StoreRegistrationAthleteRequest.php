<?php

namespace App\Http\Requests;

use App\Models\Athlete;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition
            && ($this->user()?->can('create', \App\Models\Registration::class) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
        ];
    }

    public function athlete(): Athlete
    {
        return Athlete::query()->findOrFail($this->integer('athlete_id'));
    }
}
