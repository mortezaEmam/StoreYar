<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Inventory;

use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientStock;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Domain\Exceptions\InsufficientStockForOrder;
use StoreYar\Shared\Domain\Contracts\Clock;

final class InventoryStockReservationService implements StockReservationService
{
    public function __construct(
        private StockItemRepository $stockItems,
        private Clock $clock,
    ) {}

    public function consumeForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->stockItems->findByOrganizationAndProduct(
                $organizationId,
                $line['product_id'],
            );

            if ($item === null) {
                throw new InsufficientStockForOrder(
                    $line['product_id'],
                    $line['quantity'],
                    0,
                );
            }

            try {
                $item->decrease($line['quantity'], $this->clock->now());
            } catch (InsufficientStock) {
                throw new InsufficientStockForOrder(
                    $line['product_id'],
                    $line['quantity'],
                    $item->quantity(),
                );
            }

            $this->stockItems->save($item);
        }
    }

    public function restoreForOrder(string $organizationId, array $lines): void
    {
        foreach ($lines as $line) {
            $item = $this->stockItems->findByOrganizationAndProduct(
                $organizationId,
                $line['product_id'],
            );

            if ($item === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot restore stock for missing item "%s".',
                        $line['product_id'],
                    ),
                );
            }

            $item->increase($line['quantity'], $this->clock->now());
            $this->stockItems->save($item);
        }
    }
}
