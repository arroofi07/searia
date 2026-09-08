<?php

namespace App\Actions;

use App\DataTransferObjects\InvoiceCalculation;
use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotReissuePaidInvoiceException;
use App\Exceptions\EmptyInvoiceException;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use App\Models\Registration;
use App\Models\RegistrationSubmission;
use App\Services\Invoice\InvoiceNumberGenerator;
use App\Services\InvoiceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sebuah tagihan dimiliki tepat oleh salah satu dari dua pihak: pengiriman form
 * publik (`submission_id`) atau klub yang datanya diimport panitia (`club_id`).
 */
class IssueInvoice
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly InvoiceNumberGenerator $numbers,
    ) {}

    public function handle(Competition $competition, Club $club, ?CarbonInterface $dueAt = null): Invoice
    {
        return DB::transaction(function () use ($competition, $club, $dueAt): Invoice {
            $existing = Invoice::query()
                ->where('competition_id', $competition->id)
                ->where('club_id', $club->id)
                ->lockForUpdate()
                ->first();

            return $this->persist(
                $competition,
                $existing,
                $this->calculator->forClub($competition, $club),
                ['club_id' => $club->id],
                $dueAt,
            );
        });
    }

    public function forSubmission(RegistrationSubmission $submission, ?CarbonInterface $dueAt = null): Invoice
    {
        return DB::transaction(function () use ($submission, $dueAt): Invoice {
            $existing = Invoice::query()
                ->where('submission_id', $submission->id)
                ->lockForUpdate()
                ->first();

            return $this->persist(
                $submission->competition,
                $existing,
                $this->calculator->forSubmission($submission),
                ['submission_id' => $submission->id],
                $dueAt,
            );
        });
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function handleAll(Competition $competition, ?CarbonInterface $dueAt = null): Collection
    {
        $clubs = Club::query()
            ->whereHas('athletes.registrations', function ($query) use ($competition): void {
                $query->where('competition_id', $competition->id)
                    ->where('status', RegistrationStatus::Verified)
                    ->whereNull('submission_id');
            })
            ->orderBy('name')
            ->get();

        return $clubs
            ->reject(function (Club $club) use ($competition): bool {
                $existing = Invoice::query()
                    ->where('competition_id', $competition->id)
                    ->where('club_id', $club->id)
                    ->first();

                return $existing?->isPaid() ?? false;
            })
            ->map(fn (Club $club): Invoice => $this->handle($competition, $club, $dueAt))
            ->values();
    }

    /**
     * @param  array<string, int>  $owner
     */
    private function persist(
        Competition $competition,
        ?Invoice $invoice,
        InvoiceCalculation $calculation,
        array $owner,
        ?CarbonInterface $dueAt,
    ): Invoice {
        if ($invoice?->isPaid()) {
            throw new CannotReissuePaidInvoiceException('Tagihan yang sudah lunas tidak dapat diterbitkan ulang.');
        }

        if ($calculation->isEmpty() && $invoice === null) {
            throw new EmptyInvoiceException('Tidak ada entri yang bisa ditagih.');
        }

        $attributes = [
            'item_count' => $calculation->itemCount(),
            'amount' => $calculation->total(),
            'line_items' => $calculation->toArray(),
            'status' => InvoiceStatus::Unpaid,
        ];

        if ($dueAt !== null) {
            $attributes['due_at'] = $dueAt;
        }

        if ($invoice === null) {
            $invoice = Invoice::query()->create([
                ...$attributes,
                ...$owner,
                'competition_id' => $competition->id,
                'invoice_number' => $this->numbers->next($competition),
                'due_at' => $dueAt ?? now()->addDays((int) config('searia.invoice.due_days', 7)),
            ]);
        } else {
            $invoice->update($attributes);
            $invoice = $invoice->fresh();
        }

        $this->syncRegistrations($invoice, $calculation->registrationIds());

        return $invoice;
    }

    /**
     * @param  list<int>  $registrationIds
     */
    private function syncRegistrations(Invoice $invoice, array $registrationIds): void
    {
        Registration::query()
            ->where('invoice_id', $invoice->id)
            ->when(
                $registrationIds !== [],
                fn ($query) => $query->whereNotIn('id', $registrationIds),
            )
            ->update(['invoice_id' => null]);

        if ($registrationIds === []) {
            return;
        }

        Registration::query()
            ->whereIn('id', $registrationIds)
            ->update(['invoice_id' => $invoice->id]);
    }
}
