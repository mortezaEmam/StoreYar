<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Exceptions;

use StoreYar\Shared\Domain\Errors\Error;

final class ConcurrencyException extends DomainException
{
    public function __construct(Error $error)
    {
        parent::__construct($error);
    }
}
