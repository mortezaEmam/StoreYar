<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use StoreYar\Modules\Identity\Domain\Exceptions\IdentityDomainException;

final class InvalidAuthenticateUser extends IdentityDomainException
{
    public function __construct()
    {
        parent::__construct('AuthenticateUserHandler received an invalid command.');
    }
}
