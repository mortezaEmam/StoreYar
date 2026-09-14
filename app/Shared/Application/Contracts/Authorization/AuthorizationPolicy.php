<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Authorization;

use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use StoreYar\Shared\Application\Context\BusinessContext;

interface AuthorizationPolicy
{
    public function decide(
        AuthorizationSubject $subject,
        Permission $permission,
        AuthorizationResource $resource,
        BusinessContext $business,
    ): AuthorizationDecision;
}
