<?php

declare(strict_types=1);

namespace StoreYar\Shared\Support\Quantity;

final readonly class Quantity
{
    private const SCALE = 8;

    public function __construct(
        private string $value,
    ) {
        if ($this->value === '') {
            throw new \InvalidArgumentException(
                'Quantity value cannot be empty.'
            );
        }

        if (! preg_match('/^\d+(?:\.\d+)?$/', $this->value)) {
            throw new \InvalidArgumentException(
                'Quantity value must be a non-negative decimal number.'
            );
        }

        if (! extension_loaded('bcmath')) {
            throw new \RuntimeException(
                'BCMath extension is required for Quantity operations.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self(
            bcadd(
                $this->value,
                $other->value,
                self::SCALE
            ),
        );
    }

    public function subtract(self $other): self
    {
        if (
            bccomp(
                $this->value,
                $other->value,
                self::SCALE
            ) < 0
        ) {
            throw new \InvalidArgumentException(
                'Quantity cannot become negative.'
            );
        }

        return new self(
            bcsub(
                $this->value,
                $other->value,
                self::SCALE
            ),
        );
    }

    public function isZero(): bool
    {
        return bccomp(
                $this->value,
                '0',
                self::SCALE
            ) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp(
                $this->value,
                '0',
                self::SCALE
            ) > 0;
    }

    public function isNegative(): bool
    {
        return false;
    }

    public function equals(self $other): bool
    {
        return bccomp(
                $this->value,
                $other->value,
                self::SCALE
            ) === 0;
    }
}
