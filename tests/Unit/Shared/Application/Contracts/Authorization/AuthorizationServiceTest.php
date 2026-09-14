<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Authorization;

use StoreYar\Shared\Application\Contracts\AuthorizationDecision;
use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationService;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use StoreYar\Shared\Application\Context\BusinessContext;
use Tests\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    public function test_service_contract_can_authorize_subject_for_resource_in_business_context(): void
    {
        $service = new TestAuthorizationService();

        $subject = new TestAuthorizationSubject(
            id: '01USER',
            type: 'user',
        );

        $resource = new TestAuthorizationResource(
            type: 'sale',
            id: '01SALE',
        );

        $business = new TestBusinessContext(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $decision = $service->authorize(
            subject: $subject,
            permission: 'sales.create',
            resource: $resource,
            business: $business,
        );

        $this->assertTrue($decision->allowed());
        $this->assertSame(
            'authorization.test_allowed',
            $decision->code()
        );

        $this->assertSame($subject, $service->subject);
        $this->assertSame('sales.create', $service->permission);
        $this->assertSame($resource, $service->resource);
        $this->assertSame($business, $service->business);
    }
}

final class TestAuthorizationService implements AuthorizationService
{
    public ?AuthorizationSubject $subject = null;

    public ?string $permission = null;

    public ?AuthorizationResource $resource = null;

    public ?BusinessContext $business = null;

    public function authorize(
        AuthorizationSubject $subject,
        string $permission,
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

final readonly class TestBusinessContext implements BusinessContext
{
    public function __construct(
        private string $businessId,
        private ?string $branchId,
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
        return true;
    }

    public function hasBranch(): bool
    {
        return $this->branchId !== null;
    }
}
