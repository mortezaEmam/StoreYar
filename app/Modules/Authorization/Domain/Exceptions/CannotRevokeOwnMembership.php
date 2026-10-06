<?php

declare(strict_types=1);
namespace StoreYar\Modules\Authorization\Domain\Exceptions;

final class CannotRevokeOwnMembership extends AuthorizationDomainException
{
    public function __construct()
    {
        parent::__construct('You cannot revoke your own membership.');
    }
}
