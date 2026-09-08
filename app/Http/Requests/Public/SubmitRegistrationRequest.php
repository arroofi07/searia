<?php

namespace App\Http\Requests\Public;

use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('competition') instanceof Competition;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'submit_token' => ['required', 'string'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'terms' => 'persetujuan ketentuan',
        ];
    }
}
