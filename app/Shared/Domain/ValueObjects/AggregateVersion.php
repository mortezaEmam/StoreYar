<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\ValueObjects;

final readonly class AggregateVersion
{
    public function __construct(
        private int $value,
    ) {
        if ($this->value < 0) {
            throw new \InvalidArgumentException(
                'Aggregate version cannot be negative.'
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
