<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'club_id',
        'invoice_number',
        'item_count',
        'amount',
        'line_items',
        'proof_path',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'due_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'amount' => 'integer',
            'line_items' => 'array',
            'status' => InvoiceStatus::class,
            'verified_at' => 'datetime',
            'due_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function canBeReissued(): bool
    {
        return $this->status->canReissue();
    }

    public function canUploadProof(): bool
    {
        return $this->status->canUploadProof();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lines(): array
    {
        return $this->line_items ?? [];
    }
}
