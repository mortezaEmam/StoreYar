<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

interface IdGenerator
{
    public function generate(): string;
}
