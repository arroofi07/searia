<?php

namespace App\DataTransferObjects;

class InvoiceCalculation
{
    /**
     * @param  list<InvoiceLine>  $lines
     */
    public function __construct(public array $lines) {}

    public function itemCount(): int
    {
        return count($this->lines);
    }

    public function total(): int
    {
        return array_sum(array_map(
            fn (InvoiceLine $line): int => $line->subtotal(),
            $this->lines,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * @return list<int>
     */
    public function registrationIds(): array
    {
        return array_map(
            fn (InvoiceLine $line): int => $line->registrationId,
            $this->lines,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (InvoiceLine $line): array => $line->toArray(),
            $this->lines,
        );
    }
}
