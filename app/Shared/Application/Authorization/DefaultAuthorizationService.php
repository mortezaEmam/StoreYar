<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Authorization;

use StoreYar\Shared\Application\Contracts\Authorization\AuthorizationPolicy;
use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationService;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Authorization\SimplePermission;

final readonly class DefaultAuthorizationService implements AuthorizationService
{
    public function __construct(
        private AuthorizationPolicy $policy,
    ) {
    }

    public function authorize(
        AuthorizationSubject $subject,
        string $permission,
        AuthorizationResource $resource,
        BusinessContext $business,
    ): AuthorizationDecision {
        if ($permission === '') {
            return AuthorizationDecision::deny(
                code: 'authorization.permission_required',
            );
        }

        return $this->policy->decide(
            subject: $subject,
            permission: new SimplePermission($permission),
            resource: $resource,
            business: $business,
        );
    }
}


