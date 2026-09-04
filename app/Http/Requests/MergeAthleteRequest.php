<?php

namespace App\Http\Requests;

use App\Models\Athlete;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $athlete instanceof Athlete && ($this->user()?->can('merge', $athlete) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        /** @var Athlete $athlete */
        $athlete = $this->route('athlete');

        return [
            'discard_athlete_id' => [
                'required',
                'integer',
                'exists:athletes,id',
                Rule::notIn([$athlete->id]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'discard_athlete_id' => 'atlet yang digabung',
        ];
    }
}
