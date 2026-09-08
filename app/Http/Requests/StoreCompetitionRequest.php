<?php

namespace App\Http\Requests;

use App\Enums\CompetitionType;
use App\Enums\SeedingMode;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Competition::class) ?? false;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return self::baseRules();
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public static function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'venue' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_opens_at' => ['required', 'date'],
            'registration_closes_at' => ['required', 'date', 'after:registration_opens_at', 'before:start_date'],
            'technical_meeting_at' => ['nullable', 'date'],
            'type' => ['required', Rule::enum(CompetitionType::class)],
            'pool_lanes' => ['required', 'integer', 'min:4', 'max:10'],
            'pool_length' => ['required', 'integer', Rule::in([25, 50])],
            'max_events_per_athlete' => ['required', 'integer', 'min:1', 'max:20'],
            'seeding_mode' => ['required', Rule::enum(SeedingMode::class)],
            'fee_per_event' => ['sometimes', 'integer', 'min:0'],
            'late_fee_per_event' => ['sometimes', 'integer', 'min:0'],
            'fast_time_input' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fast_time_input' => $this->boolean('fast_time_input'),
            'fee_per_event' => (int) ($this->input('fee_per_event') ?? 0),
            'late_fee_per_event' => (int) ($this->input('late_fee_per_event') ?? 0),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeLabels();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeLabels(): array
    {
        return [
            'name' => 'nama kejuaraan',
            'venue' => 'tempat',
            'city' => 'kota',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'registration_opens_at' => 'pembukaan pendaftaran',
            'registration_closes_at' => 'penutupan pendaftaran',
            'technical_meeting_at' => 'technical meeting',
            'type' => 'jenis',
            'pool_lanes' => 'jumlah lintasan',
            'pool_length' => 'panjang kolam',
            'max_events_per_athlete' => 'batas nomor per atlet',
            'seeding_mode' => 'mode seeding',
            'fee_per_event' => 'biaya per nomor',
            'late_fee_per_event' => 'denda keterlambatan',
            'fast_time_input' => 'input waktu cepat',
            'description' => 'deskripsi',
        ];
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
