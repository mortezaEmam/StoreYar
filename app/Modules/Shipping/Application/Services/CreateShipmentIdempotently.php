<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Shipping\Application\Commands\CreateShipment\CreateShipmentCommand;
use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use Symfony\Component\Uid\Ulid;

final class CreateShipmentIdempotently
{
    private const OPERATION_TYPE = 'shipping.shipment.create';

    private const STATUS_STARTED = 'started';

    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private CommandBus $commands,
        private ShipmentRepository $shipments,
        private IdempotencyStore $idempotency,
    ) {
    }

    /**
     * @return array{shipment: Shipment, replayed: bool}
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
            $savedId = $existing->response()['shipment_id'] ?? null;

            if (! is_string($savedId) || $savedId === '') {
                throw new \RuntimeException('Completed idempotency record is missing shipment_id.');
            }

            $shipment = $this->shipments->findById(ShipmentId::fromString($savedId));

            if ($shipment === null || $shipment->organizationId() !== $organizationId) {
                throw new \RuntimeException('Idempotent create result references a missing shipment.');
            }

            return ['shipment' => $shipment, 'replayed' => true];
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
            throw new IdempotencyConflictException(
                'A request with this Idempotency-Key is already being processed.',
            );
        }

        try {
            /** @var Shipment $shipment */
            $shipment = $this->commands->dispatch(
                new CreateShipmentCommand(
                    organizationId: $organizationId,
                    orderId: $orderId,
                ),
            );

            $this->idempotency->store(
                new IdempotencyRecord(
                    id: $claim->id(),
                    businessId: $organizationId,
                    operationId: $operationId,
                    operationType: self::OPERATION_TYPE,
                    status: self::STATUS_COMPLETED,
                    response: ['shipment_id' => $shipment->shipmentId()->value()],
                    completedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
                ),
            );

            return ['shipment' => $shipment, 'replayed' => false];
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
