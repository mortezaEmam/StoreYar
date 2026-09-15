<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Idempotency;

use Closure;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final readonly class IdempotencyExecutor
{
    public function __construct(
        private IdempotencyStore $store,
        private UlidGenerator $idGenerator,
    ) {
    }

    /**
     * @param Closure(): array<string, mixed> $operation
     */
    public function execute(
        string $businessId,
        string $operationId,
        string $operationType,
        Closure $operation,
    ): IdempotencyResult {
        $existing = $this->store->find(
            $businessId,
            $operationId,
        );

        if ($existing !== null) {
            return $this->resultFromExisting($existing);
        }

        $processing = new IdempotencyRecord(
            id: $this->idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: $operationType,
            status: 'processing',
        );

        if (! $this->store->claim($processing)) {
            $existing = $this->store->find(
                $businessId,
                $operationId,
            );

            if ($existing === null) {
                throw new \RuntimeException(
                    'Idempotency claim was lost without an existing record.',
                );
            }

            return $this->resultFromExisting($existing);
        }

        $response = $operation();

        $completed = new IdempotencyRecord(
            id: $processing->id(),
            businessId: $processing->businessId(),
            operationId: $processing->operationId(),
            operationType: $processing->operationType(),
            status: 'completed',
            response: $response,
            completedAt: new \DateTimeImmutable(
                'now',
                new \DateTimeZone('UTC'),
            ),
        );

        $this->store->store($completed);

        return new IdempotencyResult(
            response: $response,
            replayed: false,
        );
    }

    private function resultFromExisting(
        IdempotencyRecord $existing,
    ): IdempotencyResult {
        if ($existing->status() === 'processing') {
            throw new \RuntimeException(
                'Idempotency operation is already being processed.',
            );
        }

        return new IdempotencyResult(
            response: $existing->response(),
            replayed: true,
        );
    }
}
