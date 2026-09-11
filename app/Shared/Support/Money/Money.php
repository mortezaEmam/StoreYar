<?php

declare(strict_types=1);

namespace StoreYar\Shared\Support\Money;

final readonly class Money
{
    private const SCALE = 8;

    public function __construct(
        private string $amount,
        private string $currency,
    ) {
        if ($this->amount === '') {
            throw new \InvalidArgumentException(
                'Money amount cannot be empty.'
            );
        }

        if ($this->currency === '') {
            throw new \InvalidArgumentException(
                'Money currency cannot be empty.'
            );
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $this->amount)) {
            throw new \InvalidArgumentException(
                'Money amount must be a valid decimal number.'
            );
        }

        if (! extension_loaded('bcmath')) {
            throw new \RuntimeException(
                'BCMath extension is required for Money operations.'
            );
        }
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(
            bcadd(
                $this->amount,
                $other->amount,
                self::SCALE
            ),
            $this->currency,
        );
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(
            bcsub(
                $this->amount,
                $other->amount,
                self::SCALE
            ),
            $this->currency,
        );
    }

    public function isZero(): bool
    {
        return bccomp(
                $this->amount,
                '0',
                self::SCALE
            ) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp(
                $this->amount,
                '0',
                self::SCALE
            ) > 0;
    }

    public function isNegative(): bool
    {
        return bccomp(
                $this->amount,
                '0',
                self::SCALE
            ) < 0;
    }

    public function equals(self $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp(
                $this->amount,
                $other->amount,
                self::SCALE
            ) === 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                'Money currencies must match.'
            );
        }
    }
}
