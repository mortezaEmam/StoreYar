<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Exceptions;

abstract class DomainException extends \RuntimeException
{
    abstract public function errorCode(): string;

    public function context(): array
    {
        return [];
    }
}
