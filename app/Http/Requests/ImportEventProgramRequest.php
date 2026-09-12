<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;

class ImportEventProgramRequest extends FormRequest
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
        $maxKilobytes = (int) ceil(((int) config('searia.import.max_bytes', 5_242_880)) / 1024);

        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:'.$maxKilobytes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas Excel atau CSV wajib diunggah.',
            'file.mimes' => 'Berkas harus berformat .xlsx atau .csv.',
            'file.max' => 'Berkas melebihi 5 MB.',
        ];
    }
}
