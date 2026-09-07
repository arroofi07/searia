<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PublicPageCache
{
    public static function version(): int
    {
        return (int) Cache::get('public_pages_version', 1);
    }

    public static function bump(): void
    {
        Cache::forever('public_pages_version', self::version() + 1);
    }

    public static function key(string $suffix): string
    {
        return 'public_v'.self::version().':'.$suffix;
    }
}
