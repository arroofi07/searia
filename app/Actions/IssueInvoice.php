<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotReissuePaidInvoiceException;
use App\Exceptions\EmptyInvoiceException;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use App\Models\Registration;
use App\Services\Invoice\InvoiceNumberGenerator;
use App\Services\InvoiceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IssueInvoice
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly InvoiceNumberGenerator $numbers,
    ) {}

    public function handle(Competition $competition, Club $club, ?CarbonInterface $dueAt = null): Invoice
    {
        return DB::transaction(function () use ($competition, $club, $dueAt): Invoice {
            $calculation = $this->calculator->forClub($competition, $club);

            $invoice = Invoice::query()
                ->where('competition_id', $competition->id)
                ->where('club_id', $club->id)
                ->lockForUpdate()
                ->first();

            if ($invoice?->isPaid()) {
                throw new CannotReissuePaidInvoiceException('Tagihan yang sudah lunas tidak dapat diterbitkan ulang.');
            }

            if ($calculation->isEmpty() && $invoice === null) {
                throw new EmptyInvoiceException('Tidak ada entri terverifikasi untuk ditagih.');
            }

            $changed = $invoice === null
                || $invoice->amount !== $calculation->total()
                || $invoice->item_count !== $calculation->itemCount();

            $status = InvoiceStatus::Unpaid;
            if ($invoice !== null && $invoice->status === InvoiceStatus::WaitingVerification && ! $changed) {
                $status = InvoiceStatus::WaitingVerification;
            }

            $attributes = [
                'item_count' => $calculation->itemCount(),
                'amount' => $calculation->total(),
                'line_items' => $calculation->toArray(),
                'status' => $status,
            ];

            if ($dueAt !== null) {
                $attributes['due_at'] = $dueAt;
            }

            if ($invoice === null) {
                $invoice = Invoice::query()->create([
                    ...$attributes,
                    'competition_id' => $competition->id,
                    'club_id' => $club->id,
                    'invoice_number' => $this->numbers->next($competition),
                    'due_at' => $dueAt ?? now()->addDays((int) config('searia.invoice.due_days', 7)),
                ]);
            } else {
                $invoice->update($attributes);
                $invoice = $invoice->fresh();
            }

            $this->syncRegistrations($invoice, $calculation->registrationIds());

            return $invoice;
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
                    ->where('status', RegistrationStatus::Verified);
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
