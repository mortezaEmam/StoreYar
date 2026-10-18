<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\ValueObjects;

use StoreYar\Shared\Support\Money\MinorMoney;

/**
 * @deprecated Prefer {@see MinorMoney}. Kept as Sales-facing alias API.
 */
final readonly class Money
{
    private function __construct(
        private MinorMoney $inner,
    ) {
    }

    public static function of(int $amount, string $currency = 'IRR'): self
    {
        return new self(MinorMoney::of($amount, $currency));
    }

    public function amount(): int
    {
        return $this->inner->amount();
    }

    public function currency(): string
    {
        return $this->inner->currency();
    }

    public function multiply(int $quantity): self
    {
        return new self($this->inner->multiply($quantity));
    }

    public function add(self $other): self
    {
        return new self($this->inner->add($other->inner));
    }

    public function equals(self $other): bool
    {
        return $this->inner->equals($other->inner);
    }

    public function toMinorMoney(): MinorMoney
    {
        return $this->inner;
    }
}
