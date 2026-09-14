<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Authorization;

use StoreYar\Shared\Application\Contracts\AuthorizationResource;
use StoreYar\Shared\Application\Contracts\AuthorizationSubject;
use Tests\TestCase;

final class AuthorizationContractsTest extends TestCase
{
    public function test_subject_exposes_identity_and_type(): void
    {
        $subject = new TestAuthorizationSubject(
            id: '01USER',
            type: 'user',
        );

        $this->assertSame('01USER', $subject->id());
        $this->assertSame('user', $subject->type());
    }

    public function test_resource_exposes_type_and_optional_id(): void
    {
        $resource = new TestAuthorizationResource(
            type: 'sale',
            id: '01SALE',
        );

        $this->assertSame('sale', $resource->type());
        $this->assertSame('01SALE', $resource->id());
    }

    public function test_resource_can_represent_type_only(): void
    {
        $resource = new TestAuthorizationResource(
            type: 'sales',
            id: null,
        );

        $this->assertSame('sales', $resource->type());
        $this->assertNull($resource->id());
    }
}

final readonly class TestAuthorizationSubject implements AuthorizationSubject
{
    public function __construct(
        private string $id,
        private string $type,
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

final readonly class TestAuthorizationResource implements AuthorizationResource
{
    public function __construct(
        private string $type,
        private ?string $id,
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
