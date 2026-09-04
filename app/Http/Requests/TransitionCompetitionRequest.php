<?php

namespace App\Http\Requests;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        if (! $competition instanceof Competition) {
            return false;
        }

        $to = CompetitionStatus::tryFrom((string) $this->input('status'));

        if ($to === null) {
            return $this->user()?->can('transition', $competition) ?? false;
        }

        if ($competition->status->isBackward($to)) {
            return $this->user()?->can('revert', $competition) ?? false;
        }

        return $this->user()?->can('transition', $competition) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        /** @var Competition|null $competition */
        $competition = $this->route('competition');
        $to = CompetitionStatus::tryFrom((string) $this->input('status'));
        $needsReason = $competition instanceof Competition && $to instanceof CompetitionStatus
            && $competition->status->isBackward($to);

        return [
            'status' => ['required', Rule::enum(CompetitionStatus::class)],
            'reason' => [$needsReason ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status',
            'reason' => 'alasan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Perpindahan mundur wajib disertai alasan.',
        ];
    }
}
