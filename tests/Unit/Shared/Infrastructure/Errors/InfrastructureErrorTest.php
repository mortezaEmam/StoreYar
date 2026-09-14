<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Errors;

use StoreYar\Shared\Domain\Errors\ErrorCategory;
use StoreYar\Shared\Infrastructure\Errors\InfrastructureError;
use StoreYar\Shared\Infrastructure\Errors\IntegrationError;
use Tests\TestCase;

final class InfrastructureErrorTest extends TestCase
{
    public function test_infrastructure_error_exposes_its_error_data(): void
    {
        $error = new TestInfrastructureError(
            code: 'system.database_failure',
            message: 'خطای داخلی زیرساخت.',
            details: [
                'operation' => 'read',
            ],
        );

        $this->assertSame(
            'system.database_failure',
            $error->code()
        );

        $this->assertSame(
            'خطای داخلی زیرساخت.',
            $error->message()
        );

        $this->assertSame(
            ErrorCategory::INFRASTRUCTURE,
            $error->category()
        );

        $this->assertSame(
            ['operation' => 'read'],
            $error->details()
        );
    }

    public function test_integration_error_has_integration_category(): void
    {
        $error = new IntegrationError(
            code: 'payment.gateway_unavailable',
            message: 'سرویس پرداخت در دسترس نیست.',
            details: [
                'provider' => 'example_gateway',
            ],
        );

        $this->assertSame(
            'payment.gateway_unavailable',
            $error->code()
        );

        $this->assertSame(
            ErrorCategory::INTEGRATION,
            $error->category()
        );

        $this->assertSame(
            ['provider' => 'example_gateway'],
            $error->details()
        );
    }
}

final readonly class TestInfrastructureError extends InfrastructureError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $code,
        string $message,
        array $details = [],
    ) {
        parent::__construct(
            code: $code,
            message: $message,
            category: ErrorCategory::INFRASTRUCTURE,
            details: $details,
        );
    }
}
