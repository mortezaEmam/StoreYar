<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Idempotency;

use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;

final class InMemoryIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, IdempotencyRecord>
     */
    private array $records = [];

    public function find(
        string $businessId,
        string $operationId,
    ): ?IdempotencyRecord {
        return $this->records[$this->key(
            $businessId,
            $operationId,
        )] ?? null;
    }

    public function claim(IdempotencyRecord $record): bool
    {
        $key = $this->key(
            $record->businessId(),
            $record->operationId(),
        );

        if (isset($this->records[$key])) {
            return false;
        }

        $this->records[$key] = $record;

        return true;
    }

    public function store(IdempotencyRecord $record): void
    {
        $this->records[$this->key(
            $record->businessId(),
            $record->operationId(),
        )] = $record;
    }

    private function key(
        string $businessId,
        string $operationId,
    ): string {
        return $businessId . ':' . $operationId;
    }
}
