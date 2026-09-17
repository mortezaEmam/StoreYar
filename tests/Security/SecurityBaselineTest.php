<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Application\Authorization\DefaultAuthorizationService;
use StoreYar\Shared\Application\Authorization\SimplePermission;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use StoreYar\Shared\Application\Contracts\Authorization\AuthorizationPolicy;
use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use StoreYar\Shared\Application\Contracts\Authorization\Permission;
use StoreYar\Shared\Application\Context\BusinessContext;

final class SecurityBaselineTest extends TestCase
{
    public function test_empty_permission_is_denied(): void
    {
        $policy = new TestAuthorizationPolicy();

        $service = new DefaultAuthorizationService($policy);

        $subject = new TestAuthorizationSubject(
            id: '01USER',
            type: 'user',
        );

        $resource = new TestAuthorizationResource(
            type: 'test-resource',
            id: null,
        );

        $business = new TestBusinessContext(
            businessId: '01BUSINESS',
            branchId: null,
        );

        $decision = $service->authorize(
            subject: $subject,
            permission: '',
            resource: $resource,
            business: $business,
        );

        $this->assertTrue($decision->denied());
        $this->assertSame(
            'authorization.permission_required',
            $decision->code(),
        );

        $this->assertFalse($policy->called);
        $this->assertNull($policy->permission);
    }

    public function test_business_context_fails_closed_without_business(): void
    {
        $context = new CurrentBusinessContext();

        $this->assertFalse($context->hasBusiness());
        $this->assertFalse($context->hasBranch());

        $this->expectException(\LogicException::class);

        $context->businessId();
    }

    public function test_business_context_can_be_cleared(): void
    {
        $context = new CurrentBusinessContext();

        $context->setBusiness(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $this->assertTrue($context->hasBusiness());
        $this->assertTrue($context->hasBranch());

        $context->clear();

        $this->assertFalse($context->hasBusiness());
        $this->assertFalse($context->hasBranch());
    }

    public function test_empty_permission_is_rejected_by_simple_permission(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SimplePermission('');
    }
}

final class TestAuthorizationSubject implements AuthorizationSubject
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }
}

final class TestAuthorizationResource implements AuthorizationResource
{
    public function __construct(
        private readonly string $type,
        private readonly ?string $id,
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): ?string
    {
        return $this->id;
    }
}

final class TestBusinessContext implements BusinessContext
{
    public function __construct(
        private readonly string $businessId,
        private readonly ?string $branchId = null,
    ) {
    }

    public function businessId(): string
    {
        return $this->businessId;
    }

    public function branchId(): ?string
    {
        return $this->branchId;
    }

    public function hasBusiness(): bool
    {
        return $this->businessId !== '';
    }

    public function hasBranch(): bool
    {
        return $this->branchId !== null;
    }
}

final class TestAuthorizationPolicy implements AuthorizationPolicy
{
    public bool $called = false;

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
        $this->called = true;
        $this->subject = $subject;
        $this->permission = $permission;
        $this->resource = $resource;
        $this->business = $business;

        return AuthorizationDecision::allow(
            code: 'authorization.allowed',
        );
    }
}
