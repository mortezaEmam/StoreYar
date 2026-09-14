<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Context;

use StoreYar\Shared\Application\Context\BusinessContext;
use Tests\TestCase;

final class BusinessContextTest extends TestCase
{
    public function test_it_exposes_business_and_branch_context(): void
    {
        $context = new TestBusinessContext(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $this->assertSame(
            '01BUSINESS',
            $context->businessId()
        );

        $this->assertSame(
            '01BRANCH',
            $context->branchId()
        );

        $this->assertTrue($context->hasBusiness());
        $this->assertTrue($context->hasBranch());
    }

    public function test_branch_can_be_absent(): void
    {
        $context = new TestBusinessContext(
            businessId: '01BUSINESS',
            branchId: null,
        );

        $this->assertSame(
            '01BUSINESS',
            $context->businessId()
        );

        $this->assertNull($context->branchId());

        $this->assertTrue($context->hasBusiness());
        $this->assertFalse($context->hasBranch());
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
        return $this->businessId !== '';
    }

    public function hasBranch(): bool
    {
        return $this->branchId !== null;
    }
}
