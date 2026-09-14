<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Errors;

final readonly class GenericError implements Error
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

    public function details(): array
    {
        return $this->details;
    }
}
