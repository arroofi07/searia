<?php

namespace App\Http\Requests;

use App\Enums\ClubType;
use App\Models\Club;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Club::class) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:clubs,name'],
            'short_name' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::enum(ClubType::class)],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama klub',
            'short_name' => 'singkatan',
            'type' => 'jenis',
            'city' => 'kota',
            'province' => 'provinsi',
            'contact_name' => 'nama kontak',
            'contact_phone' => 'telepon kontak',
        ];
    }
}
