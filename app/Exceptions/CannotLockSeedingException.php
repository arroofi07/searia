<?php

namespace App\Exceptions;

use RuntimeException;

class CannotLockSeedingException extends RuntimeException
{
    /**
     * @param  list<array{event_id: int, age_group_id: int, event_number: string, event_name: string, age_group_name: string|null, label: string}>  $details
     */
    public function __construct(string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
