<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PerEventResultExport implements WithMultipleSheets
{
    public function __construct(private readonly Competition $competition) {}

    public function sheets(): array
    {
        return $this->competition->events()
            ->orderBy('event_number')
            ->get()
            ->map(fn ($event) => new ResultExport(
                $this->competition,
                event: $event,
                sheetTitle: 'Acara '.$event->event_number,
            ))
            ->all();
    }
}
