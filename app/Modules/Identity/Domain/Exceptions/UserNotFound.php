<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Exceptions;

final class UserNotFound extends IdentityDomainException
{
    public function __construct(?string $identifier = null)
    {
        parent::__construct(
            $identifier === null
                ? 'User not found.'
                : sprintf('User "%s" not found.', $identifier),
        );
    }
}
