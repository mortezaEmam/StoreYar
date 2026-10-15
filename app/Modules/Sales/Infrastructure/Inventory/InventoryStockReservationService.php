<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Inventory;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientReservedStock;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Domain\Exceptions\InsufficientStockForOrder;
use StoreYar\Shared\Domain\Contracts\Clock;

final class InventoryStockReservationService implements StockReservationService
{
    public function __construct(
        private StockItemRepository $stockItems,
        private Clock $clock,
    ) {}

    public function reserveForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);

            try {
                $item->reserve($line['quantity'], $this->clock->now());
            } catch (InsufficientAvailableStock) {
                throw new InsufficientStockForOrder(
                    $line['product_id'],
                    $line['quantity'],
                    $item->quantity(),
                );
            }

            $this->stockItems->save($item);
        }
    }

    public function releaseForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);
            $item->release($line['quantity'], $this->clock->now());
            $this->stockItems->save($item);
        }
    }

    public function commitForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);

            try {
                $item->commitReserved($line['quantity'], $this->clock->now());
            } catch (InsufficientReservedStock $e) {
                throw $e;
            }

            $this->stockItems->save($item);
        }
    }

    public function restockForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);
            $item->increase($line['quantity'], $this->clock->now());
            $this->stockItems->save($item);
        }
    }

    private function requireItem(string $organizationId, string $productId): StockItem
    {
        $item = $this->stockItems->findByOrganizationAndProduct(
            $organizationId,
            $productId,
        );

        if ($item === null) {
            throw new InsufficientStockForOrder($productId, 0, 0);
        }

        return $item;
    }
}
