<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    /** @use HasFactory<\Database\Factories\SitePageFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'body',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }
}
