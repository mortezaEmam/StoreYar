<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final readonly class ConfirmOrderIdempotently
{
    public function __construct(
        private CommandBus $commands,
        private OrderRepository $orders,
        private IdempotencyStore $idempotency,
        private UlidGenerator $idGenerator,
    ) {
    }

    /**
     * @return array{order: Order, replayed: bool}
     */
    public function execute(
        string $organizationId,
        string $orderId,
        string $idempotencyKey,
    ): array {
        $operationId = $this->operation($orderId, $idempotencyKey);
        $operationType = 'sales.order.confirm';

        $existing = $this->idempotency->find(
            $organizationId,
            $operationId,
        );

        if ($existing !== null) {
            return $this->replay($existing, $organizationId);
        }

        $processing = new IdempotencyRecord(
            id: $this->idGenerator->generate(),
            businessId: $organizationId,
            operationId: $operationId,
            operationType: $operationType,
            status: 'processing',
        );

        if (! $this->idempotency->claim($processing)) {
            $existing = $this->idempotency->find(
                $organizationId,
                $operationId,
            );

            if ($existing === null || $existing->status() === 'processing') {
                throw new IdempotencyConflictException(
                    'A request with this Idempotency-Key is already being processed.',
                );
            }

            return $this->replay($existing, $organizationId);
        }

        try {
            /** @var Order $order */
            $order = $this->commands->dispatch(
                new ConfirmOrderCommand(
                    orderId: $orderId,
                    organizationId: $organizationId,
                ),
            );

            $this->idempotency->store(
                new IdempotencyRecord(
                    id: $processing->id(),
                    businessId: $organizationId,
                    operationId: $operationId,
                    operationType: $operationType,
                    status: 'completed',
                    response: [
                        'order_id' => $order->orderId()->value(),
                    ],
                    completedAt: new DateTimeImmutable(
                        'now',
                        new DateTimeZone('UTC'),
                    ),
                ),
            );

            return [
                'order' => $order,
                'replayed' => false,
            ];
        } catch (\Throwable $exception) {
            /*
             * The current IdempotencyStore contract has no release/delete
             * method. Do not call a nonexistent method here.
             */
            throw $exception;
        }
    }

    /**
     * @return array{order: Order, replayed: true}
     */
    private function replay(
        IdempotencyRecord $record,
        string $organizationId,
    ): array {
        if ($record->status() !== 'completed') {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        $savedOrderId = $record->response()['order_id'] ?? null;

        if (! is_string($savedOrderId) || $savedOrderId === '') {
            throw new \RuntimeException(
                'Idempotency record does not contain a valid order ID.',
            );
        }

        $order = $this->orders->findById(
            OrderId::fromString($savedOrderId),
        );

        if (
            $order === null
            || $order->organizationId() !== $organizationId
        ) {
            throw new \RuntimeException(
                'Idempotent confirm result references a missing order.',
            );
        }

        return [
            'order' => $order,
            'replayed' => true,
        ];
    }



    private function operation(
        string $orderId,
        string $idempotencyKey,
    ): string {
        return substr(
            hash('sha256', $orderId.':'.$idempotencyKey),
            0,
            26,
        );
    }


}
