<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

interface IdempotencyStore
{
    public function has(string $businessId, string $operationId): bool;

    public function claim(
        string $businessId,
        string $operationId,
        string $requestHash
    ): bool;

    /**
     * @param array<string, mixed> $response
     */
    public function storeResponse(
        string $businessId,
        string $operationId,
        array $response
    ): void;

    /**
     * @return array<string, mixed>|null
     */
    public function response(
        string $businessId,
        string $operationId
    ): ?array;
}
