<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Id;

use StoreYar\Shared\Domain\Contracts\IdGenerator;
use Symfony\Component\Uid\Ulid;

final class UlidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
