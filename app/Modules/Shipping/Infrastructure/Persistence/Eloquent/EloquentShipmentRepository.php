<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\Enums\ShipmentStatus;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Domain\Contracts\Outbox;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentShipmentRepository implements ShipmentRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
        private readonly Outbox $outbox,
    ) {
    }

    public function findById(ShipmentId $id): ?Shipment
    {
        $model = ShipmentModel::query()->whereKey($id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrderId(string $organizationId, string $orderId): ?Shipment
    {
        $model = ShipmentModel::query()
            ->where('organization_id', $organizationId)
            ->where('order_id', $orderId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult {
        $base = ShipmentModel::query()->where('organization_id', $organizationId);

        $total = (clone $base)->count();

        $models = (clone $base)
            ->orderByDesc('created_at')
            ->offset($page->offset())
            ->limit($page->perPage)
            ->get();

        return new PageResult(
            items: $models->map(fn (ShipmentModel $model) => $this->toDomain($model))->all(),
            total: $total,
            page: $page->page,
            perPage: $page->perPage,
        );
    }

    public function save(Shipment $shipment): void
    {
        $model = ShipmentModel::query()
            ->whereKey($shipment->shipmentId()->value())
            ->first();

        if ($model === null) {
            ShipmentModel::query()->create([
                'id' => $shipment->shipmentId()->value(),
                'organization_id' => $shipment->organizationId(),
                'order_id' => $shipment->orderId(),
                'status' => $shipment->status()->value,
                'tracking_number' => $shipment->trackingNumber(),
                'version' => $shipment->version(),
                'created_at' => $shipment->createdAt(),
                'updated_at' => $shipment->updatedAt(),
            ]);
        } else {
            $expectedVersion = $shipment->version() - 1;

            $result = $this->versionedUpdater->update(
                table: 'shipping_shipments',
                id: $shipment->shipmentId()->value(),
                expectedVersion: $expectedVersion,
                changes: [
                    'organization_id' => $shipment->organizationId(),
                    'order_id' => $shipment->orderId(),
                    'status' => $shipment->status()->value,
                    'tracking_number' => $shipment->trackingNumber(),
                ],
            );

            if (! $result->updatedSuccessfully()) {
                throw new \RuntimeException('Shipment version conflict.');
            }
        }

        $events = $shipment->pullDomainEvents();

        if ($events !== []) {
            $this->outbox->recordMany($events);
        }
    }

    private function toDomain(ShipmentModel $model): Shipment
    {
        return Shipment::reconstitute(
            id: ShipmentId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            orderId: $model->order_id,
            status: ShipmentStatus::from($model->status),
            trackingNumber: $model->tracking_number,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
            updatedAt: DateTimeImmutable::createFromInterface($model->updated_at),
            version: (int) $model->version,
        );
    }
}
