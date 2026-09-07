<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PerEventStartListExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Competition $competition,
        private readonly bool $blankResults = false,
    ) {}

    public function sheets(): array
    {
        return $this->competition->events()
            ->orderBy('event_number')
            ->get()
            ->map(fn ($event) => new StartListExport(
                $this->competition,
                blankResults: $this->blankResults,
                sheetTitle: 'Acara '.$event->event_number,
                eventId: $event->id,
            ))
            ->all();
    }
}
