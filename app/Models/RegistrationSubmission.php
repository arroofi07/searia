<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu kali pengiriman form pendaftaran publik. Menggantikan peran akun pelatih
 * sebagai pemilik entri: panitia menghubungi pendaftar lewat kontak di sini.
 */
class RegistrationSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'athlete_id',
        'code',
        'registrant_name',
        'registrant_phone',
        'registrant_email',
        'ip_address',
    ];

    /**
     * Kode yang dibacakan pendaftar saat menghubungi panitia. Huruf dan angka yang
     * mudah tertukar (0/O, 1/I) sengaja dibuang agar tidak salah dengar di telepon.
     */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'REG-'.collect(range(1, 6))
                ->map(fn (): string => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<Athlete, $this>
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'submission_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'submission_id');
    }
}
