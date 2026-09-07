<?php

namespace App\Exceptions;

use RuntimeException;

class MissingImportColumnsException extends RuntimeException
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(public readonly array $columns)
    {
        parent::__construct('Kolom wajib tidak ditemukan: '.implode(', ', $columns));
    }
}
