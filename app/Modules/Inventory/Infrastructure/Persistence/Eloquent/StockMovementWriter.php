<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Inventory\Domain\Events\StockAdjusted;
use StoreYar\Modules\Inventory\Domain\Events\StockCommitted;
use StoreYar\Modules\Inventory\Domain\Events\StockInitialized;
use StoreYar\Modules\Inventory\Domain\Events\StockReleased;
use StoreYar\Modules\Inventory\Domain\Events\StockReserved;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

/**
 * Dual-writes domain stock events to inventory_stock_movements (TASK-009).
 */
final class StockMovementWriter
{
    /**
     * @param list<DomainEvent> $events
     */
    public function recordFromDomainEvents(array $events): void
    {
        foreach ($events as $event) {
            $row = $this->map($event);
            if ($row === null) {
                continue;
            }
            StockMovementModel::query()->create($row);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function map(DomainEvent $event): ?array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return match (true) {
            $event instanceof StockInitialized => [
                'id' => (string) new Ulid(),
                'organization_id' => $event->organizationId(),
                'stock_item_id' => $event->stockItemId(),
                'product_id' => $event->productId(),
                'movement_type' => 'initialized',
                'quantity_delta' => $event->quantity(),
                'reserved_delta' => 0,
                'quantity_after' => $event->quantity(),
                'reserved_after' => 0,
                'event_id' => $event->eventId(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => $now,
            ],
            $event instanceof StockAdjusted => [
                'id' => (string) new Ulid(),
                'organization_id' => $event->organizationId(),
                'stock_item_id' => $event->stockItemId(),
                'product_id' => $event->productId(),
                'movement_type' => $event->direction() === 'increase' ? 'increase' : 'decrease',
                'quantity_delta' => $event->direction() === 'increase'
                    ? $event->amount()
                    : -$event->amount(),
                'reserved_delta' => 0,
                'quantity_after' => $event->quantityAfter(),
                'reserved_after' => 0,
                'event_id' => $event->eventId(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => $now,
            ],
            $event instanceof StockReserved => [
                'id' => (string) new Ulid(),
                'organization_id' => $event->organizationId(),
                'stock_item_id' => $event->stockItemId(),
                'product_id' => $event->productId(),
                'movement_type' => 'reserved',
                'quantity_delta' => -$event->amount(),
                'reserved_delta' => $event->amount(),
                'quantity_after' => $event->quantityAfter(),
                'reserved_after' => $event->reservedQuantityAfter(),
                'event_id' => $event->eventId(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => $now,
            ],
            $event instanceof StockReleased => [
                'id' => (string) new Ulid(),
                'organization_id' => $event->organizationId(),
                'stock_item_id' => $event->stockItemId(),
                'product_id' => $event->productId(),
                'movement_type' => 'released',
                'quantity_delta' => $event->amount(),
                'reserved_delta' => -$event->amount(),
                'quantity_after' => $event->quantityAfter(),
                'reserved_after' => $event->reservedQuantityAfter(),
                'event_id' => $event->eventId(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => $now,
            ],
            $event instanceof StockCommitted => [
                'id' => (string) new Ulid(),
                'organization_id' => $event->organizationId(),
                'stock_item_id' => $event->stockItemId(),
                'product_id' => $event->productId(),
                'movement_type' => 'committed',
                'quantity_delta' => 0,
                'reserved_delta' => -$event->amount(),
                'quantity_after' => $event->quantityAfter(),
                'reserved_after' => $event->reservedQuantityAfter(),
                'event_id' => $event->eventId(),
                'occurred_at' => $event->occurredAt(),
                'created_at' => $now,
            ],
            default => null,
        };
    }
}
