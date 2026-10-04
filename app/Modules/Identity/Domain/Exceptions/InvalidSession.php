<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Exceptions;

final class InvalidSession extends IdentityDomainException
{
    public function __construct()
    {
        parent::__construct('Invalid session.');
    }
}
