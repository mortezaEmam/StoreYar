<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\ValueObjects;

use Symfony\Component\Uid\Ulid;

final readonly class ShipmentId
{
    private function __construct(private string $value)
    {
    }

    public static function generate(): self
    {
        return new self((string) new Ulid());
    }

    public static function fromString(string $value): self
    {
        if (! Ulid::isValid($value)) {
            throw new \InvalidArgumentException('Shipment ID must be a valid ULID.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
