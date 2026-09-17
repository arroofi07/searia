<?php

namespace App\Services;

use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;

class RegistrationDraft
{
    public function __construct(
        public Competition $competition,
        public Athlete $athlete,
        public Event $event,
        public ?string $seedTimeInput = null,
        public ?AgeGroup $ageGroupOverride = null,
        public ?string $overrideReason = null,
    ) {}
}
