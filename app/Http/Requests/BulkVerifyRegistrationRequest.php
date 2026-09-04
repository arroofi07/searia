<?php

namespace App\Http\Requests;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;

class BulkVerifyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Registration::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_ids' => ['required', 'array', 'min:1'],
            'registration_ids.*' => ['integer', 'exists:registrations,id'],
        ];
    }
}
