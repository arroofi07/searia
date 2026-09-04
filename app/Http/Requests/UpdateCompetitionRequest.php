<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition && ($this->user()?->can('update', $competition) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule|\Closure>>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        $rules = StoreCompetitionRequest::baseRules();
        $currentLanes = $competition->pool_lanes;

        $rules['pool_lanes'] = [
            'required',
            'integer',
            'min:4',
            'max:10',
            function (string $attribute, mixed $value, \Closure $fail) use ($competition, $currentLanes): void {
                if (! $competition->isDraft() && (int) $value !== $currentLanes) {
                    $fail('Jumlah lintasan tidak dapat diubah setelah kejuaraan keluar dari status draf.');
                }
            },
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return StoreCompetitionRequest::attributeLabels();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_closes_at.before' => 'Tanggal penutupan pendaftaran wajib berada sebelum tanggal lomba.',
            'pool_lanes.min' => 'Jumlah lintasan minimal 4.',
            'pool_lanes.max' => 'Jumlah lintasan maksimal 10.',
        ];
    }
}
