<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Exceptions;

final class UserAlreadyExists extends IdentityDomainException
{
    public function __construct(string $email)
    {
        parent::__construct(
            sprintf('A user with email "%s" already exists.', $email),
        );
    }
}
