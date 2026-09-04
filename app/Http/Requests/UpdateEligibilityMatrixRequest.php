<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEligibilityMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition && ($this->user()?->can('update', $competition) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'pairs' => ['present', 'array'],
            'pairs.*.event_id' => [
                'required',
                'integer',
                Rule::exists('events', 'id')->where('competition_id', $competition->id),
            ],
            'pairs.*.age_group_id' => [
                'required',
                'integer',
                Rule::exists('age_groups', 'id')->where('competition_id', $competition->id),
            ],
            'confirm_affected' => ['sometimes', 'boolean'],
        ];
    }
}
