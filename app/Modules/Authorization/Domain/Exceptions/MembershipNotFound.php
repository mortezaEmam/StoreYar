<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Exceptions;

final class MembershipNotFound extends AuthorizationDomainException
{
    public function __construct()
    {
        parent::__construct('Membership not found.');
    }
}
