<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts;

use StoreYar\Shared\Application\Context\BusinessContext;

interface AuthorizationService
{
    public function authorize(
        AuthorizationSubject $subject,
        string $permission,
        AuthorizationResource $resource,
        BusinessContext $business,
    ): AuthorizationDecision;
}
