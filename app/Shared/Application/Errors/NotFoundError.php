<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Errors;

use StoreYar\Shared\Domain\Errors\ErrorCategory;

final readonly class NotFoundError extends ApplicationError
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
            category: ErrorCategory::NOT_FOUND,
            details: $details,
        );
    }
}
