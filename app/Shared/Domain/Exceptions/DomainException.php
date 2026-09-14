<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Exceptions;

use RuntimeException;
use StoreYar\Shared\Domain\Errors\Error;
use StoreYar\Shared\Domain\Errors\ErrorCategory;

abstract class DomainException extends RuntimeException
{
    public function __construct(
        private readonly Error $error,
    ) {
        parent::__construct($error->message());
    }

    public function error(): Error
    {
        return $this->error;
    }

    public function errorCode(): string
    {
        return $this->error->code();
    }

    public function category(): ErrorCategory
    {
        return $this->error->category();
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->error->details();
    }
}
