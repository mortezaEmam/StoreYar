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
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use Symfony\Component\Uid\Ulid;

final class ConfirmOrderIdempotently
{
    private const OPERATION_TYPE = 'sales.order.confirm';

    private const STATUS_STARTED = 'started';

    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private CommandBus $commands,
        private OrderRepository $orders,
        private IdempotencyStore $idempotency,
    ) {}

    /**
     * @return array{order: Order, replayed: bool}
     */
    public function execute(
        string $organizationId,
        string $orderId,
        string $idempotencyKey,
    ): array {
        $operationId = IdempotencyKey::operationId(
            self::OPERATION_TYPE,
            $organizationId,
            $orderId,
            $idempotencyKey,
        );

        $existing = $this->idempotency->find($organizationId, $operationId);

        if ($existing !== null && $existing->status() === self::STATUS_COMPLETED) {
            $payload = $existing->response();
            $savedOrderId = $payload['order_id'] ?? null;

            if (! is_string($savedOrderId) || $savedOrderId === '') {
                throw new \RuntimeException('Completed idempotency record is missing order_id.');
            }

            $order = $this->orders->findById(OrderId::fromString($savedOrderId));

            if ($order === null || $order->organizationId() !== $organizationId) {
                throw new \RuntimeException('Idempotent confirm result references a missing order.');
            }

            return [
                'order' => $order,
                'replayed' => true,
            ];
        }

        if ($existing !== null && $existing->status() === self::STATUS_STARTED) {
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        $claim = new IdempotencyRecord(
            id: (string) new Ulid(),
            businessId: $organizationId,
            operationId: $operationId,
            operationType: self::OPERATION_TYPE,
            status: self::STATUS_STARTED,
            response: [],
            completedAt: null,
        );

        if (! $this->idempotency->claim($claim)) {
            // Lost the race: re-read
            $again = $this->idempotency->find($organizationId, $operationId);

            if ($again !== null && $again->status() === self::STATUS_COMPLETED) {
                $payload = $again->response();
                $savedOrderId = $payload['order_id'] ?? null;

                if (is_string($savedOrderId) && $savedOrderId !== '') {
                    $order = $this->orders->findById(OrderId::fromString($savedOrderId));

                    if ($order !== null && $order->organizationId() === $organizationId) {
                        return [
                            'order' => $order,
                            'replayed' => true,
                        ];
                    }
                }
            }

            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
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
                    id: $claim->id(),
                    businessId: $organizationId,
                    operationId: $operationId,
                    operationType: self::OPERATION_TYPE,
                    status: self::STATUS_COMPLETED,
                    response: ['order_id' => $order->orderId()->value()],
                    completedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
                ),
            );

            return [
                'order' => $order,
                'replayed' => false,
            ];
        } catch (\Throwable $e) {
            // Leave started row as-is or delete if you have a release path.
            // Retry with same key after failure needs either delete or status=failed.
            // Prefer marking failed if your schema allows; otherwise document client must use new key.
            throw $e;
        }
    }
}
