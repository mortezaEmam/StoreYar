<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\ValueObjects;

final readonly class CorrelationId
{
    public function __construct(
        private string $value,
    ) {
        if ($this->value === '') {
            throw new \InvalidArgumentException(
                'Correlation ID cannot be empty.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
