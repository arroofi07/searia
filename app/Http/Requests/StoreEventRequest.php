<?php

namespace App\Http\Requests;

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
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
        $event = $this->route('event');
        $ignoreId = $event instanceof Event ? $event->id : null;

        return [
            'event_number' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
                Rule::unique('events', 'event_number')
                    ->where('competition_id', $competition->id)
                    ->ignore($ignoreId),
            ],
            'gender' => ['required', Rule::enum(EventGender::class)],
            'distance' => ['required', 'integer', Rule::in([25, 50, 100, 200, 400])],
            'stroke' => ['required', Rule::enum(Stroke::class)],
            'equipment' => ['required', Rule::enum(Equipment::class)],
            'session' => ['required', 'integer', 'min:1', 'max:10'],
            'create_pair' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('create_pair')) {
                return;
            }

            /** @var Competition $competition */
            $competition = $this->route('competition');
            $partnerNumber = $this->integer('event_number') + 1;

            $taken = Event::query()
                ->where('competition_id', $competition->id)
                ->where('event_number', $partnerNumber)
                ->exists();

            if ($taken) {
                $validator->errors()->add(
                    'event_number',
                    "Nomor pasangan {$partnerNumber} sudah dipakai di kejuaraan ini.",
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'event_number' => 'nomor acara',
            'gender' => 'gender',
            'distance' => 'jarak',
            'stroke' => 'gaya',
            'equipment' => 'alat bantu',
            'session' => 'sesi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_number.unique' => 'Nomor acara ini sudah dipakai di kejuaraan ini.',
        ];
    }
}
