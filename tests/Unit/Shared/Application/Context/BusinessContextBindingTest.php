<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Context;

use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use Tests\TestCase;

final class BusinessContextBindingTest extends TestCase
{
    public function test_business_context_resolves_to_current_business_context(): void
    {
        $context = $this->app->make(BusinessContext::class);

        $this->assertInstanceOf(
            CurrentBusinessContext::class,
            $context
        );
    }

    public function test_business_context_is_scoped(): void
    {
        $first = $this->app->make(BusinessContext::class);

        $first->setBusiness(
            businessId: '01BUSINESS',
            branchId: '01BRANCH',
        );

        $this->app->forgetScopedInstances();

        $second = $this->app->make(BusinessContext::class);

        $this->assertFalse($second->hasBusiness());
        $this->assertFalse($second->hasBranch());
    }
}
