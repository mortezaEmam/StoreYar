<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Results;

final readonly class Result
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private bool $success,
        private array $data,
        private ?string $errorCode = null,
        private ?string $errorMessage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data = []): self
    {
        return new self(
            success: true,
            data: $data,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function failure(
        string $errorCode,
        string $errorMessage,
        array $data = [],
    ): self {
        return new self(
            success: false,
            data: $data,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
