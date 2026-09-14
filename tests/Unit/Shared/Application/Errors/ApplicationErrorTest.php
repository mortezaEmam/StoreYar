<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Errors;

use StoreYar\Shared\Application\Errors\AuthorizationError;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Application\Errors\ConflictError;
use StoreYar\Shared\Application\Errors\NotFoundError;
use StoreYar\Shared\Application\Errors\ValidationError;
use StoreYar\Shared\Domain\Errors\ErrorCategory;
use Tests\TestCase;

final class ApplicationErrorTest extends TestCase
{
    public function test_validation_error_has_validation_category(): void
    {
        $error = new ValidationError(
            code: 'validation.invalid_input',
            message: 'اطلاعات وارد شده صحیح نیست.',
            details: [
                'field' => 'name',
            ],
        );

        $this->assertSame(
            'validation.invalid_input',
            $error->code()
        );

        $this->assertSame(
            'اطلاعات وارد شده صحیح نیست.',
            $error->message()
        );

        $this->assertSame(
            ErrorCategory::VALIDATION,
            $error->category()
        );

        $this->assertSame(
            ['field' => 'name'],
            $error->details()
        );
    }

    public function test_not_found_error_has_not_found_category(): void
    {
        $error = new NotFoundError(
            code: 'catalog.product_not_found',
            message: 'محصول پیدا نشد.',
        );

        $this->assertSame(
            ErrorCategory::NOT_FOUND,
            $error->category()
        );

        $this->assertSame([], $error->details());
    }

    public function test_conflict_error_has_conflict_category(): void
    {
        $error = new ConflictError(
            code: 'sales.sale_already_completed',
            message: 'فروش قبلاً تکمیل شده است.',
        );

        $this->assertSame(
            ErrorCategory::CONFLICT,
            $error->category()
        );
    }

    public function test_authorization_error_has_authorization_category(): void
    {
        $error = new AuthorizationError(
            code: 'authorization.forbidden',
            message: 'دسترسی مجاز نیست.',
        );

        $this->assertSame(
            ErrorCategory::AUTHORIZATION,
            $error->category()
        );
    }

    public function test_concurrency_error_has_concurrency_category(): void
    {
        $error = new ConcurrencyError(
            code: 'sales.sale_version_conflict',
            message: 'رکورد توسط درخواست دیگری تغییر کرده است.',
            details: [
                'expected_version' => 3,
                'actual_version' => 4,
            ],
        );

        $this->assertSame(
            ErrorCategory::CONCURRENCY,
            $error->category()
        );

        $this->assertSame(
            [
                'expected_version' => 3,
                'actual_version' => 4,
            ],
            $error->details()
        );
    }
}
