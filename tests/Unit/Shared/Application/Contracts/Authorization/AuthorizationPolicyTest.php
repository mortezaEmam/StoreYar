<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Authorization;

use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use StoreYar\Shared\Application\Contracts\Authorization\AuthorizationPolicy;
use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use StoreYar\Shared\Application\Contracts\Authorization\Permission;
use StoreYar\Shared\Application\Context\BusinessContext;
use Tests\TestCase;

final class AuthorizationPolicyTest extends TestCase
{
    public function test_policy_receives_complete_authorization_context(): void
    {
        $policy = new TestAuthorizationPolicy();

        $subject = new TestAuthorizationSubject(
            id: '01USER',
            type: 'user',
        );

        $permission = new TestPermission(
            code: 'sales.create',
        );

        $resource = new TestAuthorizationResource(
            type: 'sale',
            id: null,
        );

        $business = new TestBusinessContext(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $decision = $policy->decide(
            subject: $subject,
            permission: $permission,
            resource: $resource,
            business: $business,
        );

        $this->assertTrue($decision->allowed());

        $this->assertSame($subject, $policy->subject);
        $this->assertSame($permission, $policy->permission);
        $this->assertSame($resource, $policy->resource);
        $this->assertSame($business, $policy->business);
    }
}

final class TestAuthorizationPolicy implements AuthorizationPolicy
{
    public ?AuthorizationSubject $subject = null;

    public ?Permission $permission = null;

    public ?AuthorizationResource $resource = null;

    public ?BusinessContext $business = null;

    public function decide(
        AuthorizationSubject $subject,
        Permission $permission,
        AuthorizationResource $resource,
        BusinessContext $business,
    ): AuthorizationDecision {
        $this->subject = $subject;
        $this->permission = $permission;
        $this->resource = $resource;
        $this->business = $business;

        return AuthorizationDecision::allow(
            code: 'authorization.test_allowed',
        );
    }
}

final readonly class TestPermission implements Permission
{
    public function __construct(
        private string $code,
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }
}
