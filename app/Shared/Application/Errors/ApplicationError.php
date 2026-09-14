<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Errors;

use StoreYar\Shared\Domain\Errors\Error;
use StoreYar\Shared\Domain\Errors\ErrorCategory;

abstract readonly class ApplicationError implements Error
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private string $code,
        private string $message,
        private ErrorCategory $category,
        private array $details = [],
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function category(): ErrorCategory
    {
        return $this->category;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
