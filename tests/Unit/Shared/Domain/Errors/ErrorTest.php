<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Errors;

use StoreYar\Shared\Domain\Errors\ErrorCategory;
use StoreYar\Shared\Domain\Errors\GenericError;
use Tests\TestCase;

final class ErrorTest extends TestCase
{
    public function test_it_creates_a_generic_error(): void
    {
        $error = new GenericError(
            code: 'inventory.insufficient_stock',
            message: 'موجودی کافی نیست.',
            category: ErrorCategory::DOMAIN,
            details: [
                'sku_id' => '01ABC',
                'requested' => '10',
                'available' => '4',
            ],
        );

        $this->assertSame(
            'inventory.insufficient_stock',
            $error->code()
        );

        $this->assertSame(
            'موجودی کافی نیست.',
            $error->message()
        );

        $this->assertSame(
            ErrorCategory::DOMAIN,
            $error->category()
        );

        $this->assertSame(
            [
                'sku_id' => '01ABC',
                'requested' => '10',
                'available' => '4',
            ],
            $error->details()
        );
    }

    public function test_error_category_values_are_stable(): void
    {
        $this->assertSame(
            'domain',
            ErrorCategory::DOMAIN->value
        );

        $this->assertSame(
            'validation',
            ErrorCategory::VALIDATION->value
        );

        $this->assertSame(
            'authorization',
            ErrorCategory::AUTHORIZATION->value
        );

        $this->assertSame(
            'not_found',
            ErrorCategory::NOT_FOUND->value
        );

        $this->assertSame(
            'conflict',
            ErrorCategory::CONFLICT->value
        );

        $this->assertSame(
            'concurrency',
            ErrorCategory::CONCURRENCY->value
        );

        $this->assertSame(
            'infrastructure',
            ErrorCategory::INFRASTRUCTURE->value
        );

        $this->assertSame(
            'integration',
            ErrorCategory::INTEGRATION->value
        );
    }
}
