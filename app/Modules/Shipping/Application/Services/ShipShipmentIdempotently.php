<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Shipping\Application\Commands\ShipShipment\ShipShipmentCommand;
use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Application\Idempotency\IdempotencyKey;
use StoreYar\Shared\Domain\Exceptions\IdempotencyConflictException;
use Symfony\Component\Uid\Ulid;

final class ShipShipmentIdempotently
{
    private const OPERATION_TYPE = 'shipping.shipment.ship';

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
        string $shipmentId,
        string $trackingNumber,
        string $idempotencyKey,
    ): array {
        $operationId = IdempotencyKey::operationId(
            self::OPERATION_TYPE,
            $organizationId,
            $shipmentId,
            $trackingNumber,
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
                throw new \RuntimeException('Idempotent ship result references a missing shipment.');
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
                new ShipShipmentCommand(
                    organizationId: $organizationId,
                    shipmentId: $shipmentId,
                    trackingNumber: $trackingNumber,
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
