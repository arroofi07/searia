<?php

namespace App\Services\AgeGroupImport;

class AgeGroupImportParser
{
    public function parseCode(string $value): ?string
    {
        $code = trim($value);

        if ($code === '' || mb_strlen($code) > 10) {
            return null;
        }

        return $code;
    }

    public function parseName(string $value): ?string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));

        if ($normalized === '' || mb_strlen($normalized) > 50) {
            return null;
        }

        return $normalized;
    }

    public function parseDisplayCode(string $value): ?string
    {
        $code = trim($value);

        if ($code === '') {
            return null;
        }

        return mb_strlen($code) <= 10 ? $code : null;
    }

    public function parseYear(string $value): ?int
    {
        $trimmed = trim($value);

        if ($trimmed === '' || preg_match('/^\d{4}$/', $trimmed) !== 1) {
            return null;
        }

        return (int) $trimmed;
    }

    public function parseSort(string $value): ?int
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $trimmed) !== 1) {
            return null;
        }

        $sort = (int) $trimmed;

        return $sort >= 1 && $sort <= 99 ? $sort : null;
    }
}
