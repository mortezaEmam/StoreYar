<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Exceptions;

use StoreYar\Shared\Domain\Errors\ErrorCategory;
use StoreYar\Shared\Domain\Errors\GenericError;
use StoreYar\Shared\Domain\Exceptions\DomainException;
use Tests\TestCase;

final class DomainExceptionTest extends TestCase
{
    public function test_it_exposes_the_underlying_error(): void
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

        $exception = new TestDomainException($error);

        $this->assertSame($error, $exception->error());

        $this->assertSame(
            'inventory.insufficient_stock',
            $exception->errorCode()
        );

        $this->assertSame(
            ErrorCategory::DOMAIN,
            $exception->category()
        );

        $this->assertSame(
            'موجودی کافی نیست.',
            $exception->getMessage()
        );

        $this->assertSame(
            [
                'sku_id' => '01ABC',
                'requested' => '10',
                'available' => '4',
            ],
            $exception->context()
        );
    }
}

final class TestDomainException extends DomainException
{
}
