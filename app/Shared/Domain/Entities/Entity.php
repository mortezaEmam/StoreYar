<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Entities;

abstract class Entity
{
    public function __construct(
        private readonly string $id,
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException('Entity ID cannot be empty.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function equals(Entity $other): bool
    {
        return $this->id === $other->id()
            && static::class === $other::class;
    }
}
