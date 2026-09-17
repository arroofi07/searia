<?php

namespace App\Exceptions;

use RuntimeException;

class CannotOverrideAgeGroupException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $validationCode = 'V-09',
    ) {
        parent::__construct($message);
    }
}
