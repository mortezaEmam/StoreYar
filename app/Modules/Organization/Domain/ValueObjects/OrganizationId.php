<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\ValueObjects;

use Symfony\Component\Uid\Ulid;

final readonly class OrganizationId
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function generate(): self
    {
        return new self((string) new Ulid());
    }

    public static function fromString(string $value): self
    {
        if (! Ulid::isValid($value)) {
            throw new \InvalidArgumentException(
                'Organization ID must be a valid ULID.',
            );
        }

        return new self($value);
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
