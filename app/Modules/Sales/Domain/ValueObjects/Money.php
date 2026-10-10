<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\ValueObjects;

/**
 * Money stored as integer minor units (e.g. 10000 = 100.00 in currency with 2 decimals).
 * Currency is fixed per deployment for now; extend later if multi-currency is required.
 */
final readonly class Money
{
    private function __construct(
        private int $amount,
        private string $currency,
    ) {
    }

    public static function of(int $amount, string $currency = 'IRR'): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }

        if (trim($currency) === '') {
            throw new \InvalidArgumentException('Currency is required.');
        }

        return new self($amount, strtoupper($currency));
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self($this->amount * $quantity, $this->currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch.');
        }
    }
}
