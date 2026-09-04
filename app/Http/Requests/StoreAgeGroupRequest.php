<?php

namespace App\Http\Requests;

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Rules\NonOverlappingBirthYearRange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return $competition instanceof Competition && ($this->user()?->can('update', $competition) ?? false);
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');
        $ageGroup = $this->route('age_group');
        $ignoreId = $ageGroup instanceof AgeGroup ? $ageGroup->id : null;
        $currentYear = (int) now()->year;

        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('age_groups', 'code')
                    ->where('competition_id', $competition->id)
                    ->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:50'],
            'display_code' => ['nullable', 'string', 'max:10'],
            'birth_year_start' => ['required', 'integer', 'min:1950', 'max:'.$currentYear],
            'birth_year_end' => [
                'required',
                'integer',
                'min:1950',
                'max:'.($currentYear + 1),
                new NonOverlappingBirthYearRange(
                    $competition->id,
                    $this->integer('birth_year_start'),
                    $ignoreId,
                ),
            ],
            'sort_order' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'kode',
            'name' => 'nama',
            'display_code' => 'label cetak',
            'birth_year_start' => 'tahun lahir awal',
            'birth_year_end' => 'tahun lahir akhir',
            'sort_order' => 'urutan',
        ];
    }
}
