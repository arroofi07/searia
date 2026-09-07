<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotCancelImportBatchException;
use App\Services\Import\ImportValidationResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportBatch extends Model
{
    /** @use HasFactory<\Database\Factories\ImportBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'user_id',
        'original_filename',
        'stored_path',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'status',
        'errors',
        'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'status' => ImportStatus::class,
            'errors' => 'array',
            'committed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function result(): ImportValidationResult
    {
        return ImportValidationResult::fromArray($this->errors ?? []);
    }

    public function storeResult(ImportValidationResult $result): void
    {
        $this->update([
            'total_rows' => $result->read,
            'valid_rows' => $result->valid,
            'invalid_rows' => $result->invalid,
            'errors' => $result->toArray(),
            'status' => ImportStatus::Validated,
        ]);
    }

    public function markFailed(string $message): void
    {
        $payload = $this->errors ?? [];
        $payload['file_error'] = $message;

        $this->update([
            'status' => ImportStatus::Failed,
            'errors' => $payload,
        ]);
    }

    public function cancel(): void
    {
        if ($this->registrations()->where('status', RegistrationStatus::Verified)->exists()) {
            throw new CannotCancelImportBatchException(
                'Batch tidak dapat dibatalkan karena sebagian entri sudah diverifikasi.',
            );
        }

        DB::transaction(function (): void {
            $this->registrations()->delete();
            $this->update(['status' => ImportStatus::Cancelled]);
        });
    }

    public function deleteStoredFile(): void
    {
        if ($this->stored_path !== '' && Storage::disk('local')->exists($this->stored_path)) {
            Storage::disk('local')->delete($this->stored_path);
        }
    }
}
