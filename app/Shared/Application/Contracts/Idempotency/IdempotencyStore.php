<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Idempotency;

interface IdempotencyStore
{
    public function find(
        string $businessId,
        string $operationId,
    ): ?IdempotencyRecord;

    /**
     * Attempts to claim an operation for processing.
     *
     * Returns true only when this request successfully creates
     * the processing record.
     */
    public function claim(IdempotencyRecord $record): bool;

    public function store(IdempotencyRecord $record): void;
}
