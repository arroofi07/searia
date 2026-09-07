<?php

namespace App\DataTransferObjects;

class ParticipantRow
{
    public function __construct(
        public int $excelRow,
        public string $no,
        public string $fullName,
        public string $gender,
        public string $birthYear,
        public string $clubName,
        public string $city,
        public string $eventCode,
        public string $seedTime,
        public ?int $mappedClubId = null,
        public ?int $mappedAthleteId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function fromFields(array $fields, int $excelRow): self
    {
        return new self(
            excelRow: $excelRow,
            no: self::stringify($fields['no'] ?? ''),
            fullName: self::stringify($fields['full_name'] ?? $fields['nama_lengkap'] ?? ''),
            gender: self::stringify($fields['gender'] ?? $fields['l_p'] ?? ''),
            birthYear: self::stringify($fields['birth_year'] ?? $fields['tahun_lahir'] ?? ''),
            clubName: self::stringify($fields['club_name'] ?? $fields['klub_sekolah'] ?? ''),
            city: self::stringify($fields['city'] ?? $fields['kabupaten_kota'] ?? ''),
            eventCode: self::stringify($fields['event_code'] ?? $fields['kode_acara'] ?? ''),
            seedTime: self::stringify($fields['seed_time'] ?? $fields['catatan_waktu'] ?? ''),
            mappedClubId: isset($fields['mapped_club_id']) ? (int) $fields['mapped_club_id'] : null,
            mappedAthleteId: isset($fields['mapped_athlete_id']) ? (int) $fields['mapped_athlete_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'excel_row' => $this->excelRow,
            'no' => $this->no,
            'full_name' => $this->fullName,
            'gender' => $this->gender,
            'birth_year' => $this->birthYear,
            'club_name' => $this->clubName,
            'city' => $this->city,
            'event_code' => $this->eventCode,
            'seed_time' => $this->seedTime,
            'mapped_club_id' => $this->mappedClubId,
            'mapped_athlete_id' => $this->mappedAthleteId,
        ];
    }

    public static function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) === $value) {
                return (string) (int) $value;
            }

            return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
        }

        return trim((string) $value);
    }

    public function athleteKey(): string
    {
        return mb_strtoupper($this->fullName).'|'.$this->birthYear.'|'.mb_strtoupper($this->clubName);
    }

    public function eventAthleteKey(): string
    {
        return $this->athleteKey().'|'.$this->eventCode;
    }
}
