<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Errors;

use StoreYar\Shared\Domain\Errors\ErrorCategory;

final readonly class AuthorizationError extends ApplicationError
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
            category: ErrorCategory::AUTHORIZATION,
            details: $details,
        );
    }
}
