<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Context;

use LogicException;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use Tests\TestCase;

final class CurrentBusinessContextTest extends TestCase
{
    public function test_it_can_set_business_without_branch(): void
    {
        $context = new CurrentBusinessContext();

        $context->setBusiness('01BUSINESS');

        $this->assertTrue($context->hasBusiness());
        $this->assertFalse($context->hasBranch());

        $this->assertSame(
            '01BUSINESS',
            $context->businessId()
        );

        $this->assertNull($context->branchId());
    }

    public function test_it_can_set_business_with_branch(): void
    {
        $context = new CurrentBusinessContext();

        $context->setBusiness(
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

    public function test_it_can_clear_context(): void
    {
        $context = new CurrentBusinessContext();

        $context->setBusiness(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $context->clear();

        $this->assertFalse($context->hasBusiness());
        $this->assertFalse($context->hasBranch());
    }

    public function test_business_id_requires_initialized_context(): void
    {
        $context = new CurrentBusinessContext();

        $this->expectException(LogicException::class);

        $context->businessId();
    }

    public function test_empty_business_id_is_rejected(): void
    {
        $context = new CurrentBusinessContext();

        $this->expectException(\InvalidArgumentException::class);

        $context->setBusiness('');
    }


    public function test_it_rejects_empty_branch_id(): void
    {
        $context = new CurrentBusinessContext();

        $this->expectException(\InvalidArgumentException::class);

        $context->setBusiness(
            businessId: '01BUSINESS',
            branchId: '',
        );
    }

    public function test_clearing_context_removes_business_and_branch_state(): void
    {
        $context = new CurrentBusinessContext();

        $context->setBusiness(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $context->clear();

        $this->assertFalse($context->hasBusiness());
        $this->assertFalse($context->hasBranch());

        $this->expectException(\LogicException::class);

        $context->businessId();
    }
}
