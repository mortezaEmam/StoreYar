<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Exceptions;

final class MembershipAlreadyExists extends AuthorizationDomainException
{
    public function __construct(string $organizationId, string $userId)
    {
        parent::__construct(
            sprintf(
                'User "%s" is already a member of organization "%s".',
                $userId,
                $organizationId,
            ),
        );
    }
}
