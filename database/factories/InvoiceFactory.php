<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'club_id' => Club::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('####-####'),
            'item_count' => 1,
            'amount' => 50_000,
            'line_items' => [],
            'proof_path' => null,
            'status' => InvoiceStatus::Unpaid,
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
            'due_at' => now()->addDays(7),
            'reminder_sent_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Paid,
            'verified_at' => now(),
        ]);
    }

    public function waitingVerification(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::WaitingVerification,
            'proof_path' => 'invoices/proof.jpg',
        ]);
    }
}
