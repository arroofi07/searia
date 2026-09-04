<?php

namespace App\Support;

use App\Exceptions\InvalidSwimTimeException;

class SwimTime
{
    public function __construct(public readonly ?int $milliseconds) {}

    public static function parse(?string $input, ?bool $fastInput = null): ?self
    {
        if ($input === null) {
            return null;
        }

        $trimmed = trim($input);

        if ($trimmed === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $trimmed);
        $token = strtoupper($normalized);

        if (in_array($token, ['NT', '-', '99:99:99'], true)) {
            return null;
        }

        $fastInput ??= (bool) config('searia.swim_time.fast_input', true);

        if ($fastInput && preg_match('/^\d{4,6}$/', $normalized) === 1) {
            return new self(self::parseFastDigits($normalized));
        }

        if (preg_match('/^\d+([.]\d{1,3})?$/', $normalized) === 1) {
            return new self(self::secondsToMs($normalized));
        }

        if (preg_match('/^\d{1,2}:\d{1,2}([.]\d{1,3})?$/', $normalized) === 1) {
            [$minutes, $seconds] = explode(':', $normalized, 2);

            return new self(((int) $minutes) * 60_000 + self::secondsToMs($seconds));
        }

        if (preg_match('/^\d{1,2}:\d{1,2}:\d{1,2}([.]\d{1,3})?$/', $normalized) === 1) {
            [$hours, $minutes, $seconds] = explode(':', $normalized, 3);

            return new self(
                ((int) $hours) * 3_600_000
                + ((int) $minutes) * 60_000
                + self::secondsToMs($seconds),
            );
        }

        throw new InvalidSwimTimeException('Format waktu tidak valid, contoh yang benar 00:52.20');
    }

    public static function fromMilliseconds(?int $milliseconds): ?self
    {
        if ($milliseconds === null) {
            return null;
        }

        return new self($milliseconds);
    }

    public function format(?string $noTime = null): string
    {
        if ($this->milliseconds === null) {
            $noTime ??= (string) config('searia.swim_time.no_time_format', 'NT');

            return $noTime;
        }

        $ms = $this->milliseconds;
        $hours = intdiv($ms, 3_600_000);
        $remain = $ms % 3_600_000;
        $minutes = intdiv($remain, 60_000);
        $remain %= 60_000;
        $seconds = intdiv($remain, 1000);
        $hundredths = intdiv($remain % 1000, 10);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d.%02d', $hours, $minutes, $seconds, $hundredths);
        }

        return sprintf('%02d:%02d.%02d', $minutes, $seconds, $hundredths);
    }

    public static function formatMilliseconds(?int $milliseconds, ?string $noTime = null): string
    {
        return (new self($milliseconds))->format($noTime);
    }

    private static function parseFastDigits(string $digits): int
    {
        $length = strlen($digits);

        if ($length === 4) {
            return self::secondsToMs(substr($digits, 0, 2).'.'.substr($digits, 2, 2));
        }

        if ($length === 5) {
            return ((int) $digits[0]) * 60_000
                + self::secondsToMs(substr($digits, 1, 2).'.'.substr($digits, 3, 2));
        }

        return ((int) substr($digits, 0, 2)) * 60_000
            + self::secondsToMs(substr($digits, 2, 2).'.'.substr($digits, 4, 2));
    }

    private static function secondsToMs(string $seconds): int
    {
        [$whole, $fraction] = array_pad(explode('.', $seconds, 2), 2, '00');
        $hundredths = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole) * 1000 + ((int) $hundredths) * 10;
    }
}
