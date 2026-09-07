<?php

namespace App\Support;

class SensitiveData
{
    /** @var list<string> */
    public const KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'proof_path',
        'proof',
        'current_password',
        'token',
    ];

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public static function scrub(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $scrubbed = [];

        foreach ($values as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, self::KEYS, true) || str_contains($normalized, 'password')) {
                $scrubbed[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $scrubbed[$key] = self::scrub($value);

                continue;
            }

            $scrubbed[$key] = $value;
        }

        return $scrubbed;
    }
}
