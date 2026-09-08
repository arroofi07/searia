<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'registered_by');
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function judgedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_judge')->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isPanitia(): bool
    {
        return $this->role === UserRole::Panitia;
    }

    public function isJuri(): bool
    {
        return $this->role === UserRole::Juri;
    }

    public function managesMasterData(): bool
    {
        return $this->isSuperAdmin() || $this->isPanitia();
    }
}
