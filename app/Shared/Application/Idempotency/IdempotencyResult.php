<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Idempotency;

final readonly class IdempotencyResult
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        private array $response,
        private bool $replayed,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function response(): array
    {
        return $this->response;
    }

    public function replayed(): bool
    {
        return $this->replayed;
    }
}
