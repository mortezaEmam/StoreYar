<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Idempotency;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;

final readonly class DatabaseIdempotencyStore implements IdempotencyStore
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function find(
        string $businessId,
        string $operationId,
    ): ?IdempotencyRecord {
        $row = $this->connection
            ->table('integration_idempotency_keys')
            ->where('business_id', $businessId)
            ->where('operation_id', $operationId)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->mapRecord($row);
    }

    public function claim(IdempotencyRecord $record): bool
    {
        try {
            $this->connection
                ->table('integration_idempotency_keys')
                ->insert([
                    'id' => $record->id(),
                    'business_id' => $record->businessId(),
                    'operation_id' => $record->operationId(),
                    'operation_type' => $record->operationType(),
                    'status' => $record->status(),
                    'response' => json_encode(
                        $record->response(),
                        JSON_THROW_ON_ERROR,
                    ),
                    'completed_at' => $record->completedAt(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyException($exception)) {
                throw $exception;
            }

            return false;
        }
    }

    public function store(IdempotencyRecord $record): void
    {
        $table = $this->connection
            ->table('integration_idempotency_keys');

        $updated = $table
            ->where('business_id', $record->businessId())
            ->where('operation_id', $record->operationId())
            ->update([
                'status' => $record->status(),
                'response' => json_encode(
                    $record->response(),
                    JSON_THROW_ON_ERROR,
                ),
                'completed_at' => $record->completedAt(),
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        $table->insert([
            'id' => $record->id(),
            'business_id' => $record->businessId(),
            'operation_id' => $record->operationId(),
            'operation_type' => $record->operationType(),
            'status' => $record->status(),
            'response' => json_encode(
                $record->response(),
                JSON_THROW_ON_ERROR,
            ),
            'completed_at' => $record->completedAt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mapRecord(object $row): IdempotencyRecord
    {
        return new IdempotencyRecord(
            id: $row->id,
            businessId: $row->business_id,
            operationId: $row->operation_id,
            operationType: $row->operation_type,
            status: $row->status,
            response: $row->response === null
                ? []
                : json_decode(
                    $row->response,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                ),
            completedAt: $row->completed_at === null
                ? null
                : new DateTimeImmutable($row->completed_at),
        );
    }

    private function isDuplicateKeyException(
        QueryException $exception,
    ): bool {
        return $exception->getCode() === '23000';
    }
}
