<?php

namespace App\Exports;

use App\Enums\RegistrationStatus;
use App\Models\Competition;
use App\Models\Registration;
use App\Support\SwimTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ParticipantExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Competition $competition,
        private readonly ?int $clubId = null,
        private readonly ?int $eventId = null,
        private readonly ?string $status = null,
    ) {}

    public function title(): string
    {
        return 'PESERTA';
    }

    public function headings(): array
    {
        return [
            'NO',
            'NAMA LENGKAP',
            'L/P',
            'TAHUN LAHIR',
            'KLUB/SEKOLAH',
            'KABUPATEN/KOTA',
            'KODE ACARA',
            'CATATAN WAKTU',
            'STATUS',
            'ALASAN PENOLAKAN',
        ];
    }

    public function collection(): Collection
    {
        $rows = $this->query()->get();
        $n = 0;

        return $rows->map(function (Registration $registration) use (&$n) {
            $n++;
            $athlete = $registration->athlete;
            $club = $athlete?->club;

            return [
                (string) $n,
                (string) $athlete?->full_name,
                (string) ($athlete?->gender?->value ?? ''),
                (string) $athlete?->birth_year,
                (string) $club?->name,
                (string) $club?->city,
                (string) $registration->event?->event_number,
                SwimTime::formatMilliseconds($registration->seed_time_ms),
                (string) ($registration->status?->value ?? ''),
                (string) ($registration->rejection_reason ?? ''),
            ];
        });
    }

    /**
     * @return Builder<Registration>
     */
    private function query(): Builder
    {
        $query = Registration::query()
            ->with(['athlete.club', 'event'])
            ->where('competition_id', $this->competition->id)
            ->orderBy('id');

        if ($this->clubId !== null) {
            $query->whereHas('athlete', fn ($q) => $q->where('club_id', $this->clubId));
        }

        if ($this->eventId !== null) {
            $query->where('event_id', $this->eventId);
        }

        if ($this->status !== null && $this->status !== '') {
            $query->where('status', RegistrationStatus::from($this->status));
        }

        return $query;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
