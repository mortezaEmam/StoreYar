<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Inventory;

use StoreYar\Modules\Inventory\Application\Contracts\OrderStockService;
use StoreYar\Modules\Inventory\Application\Exceptions\InsufficientAvailableStockException;
use StoreYar\Modules\Inventory\Application\Exceptions\InsufficientReservedStockException;
use StoreYar\Modules\Inventory\Application\Exceptions\StockItemNotFoundException;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Domain\Exceptions\InsufficientStockForOrder;

/**
 * Anti-corruption adapter: Sales Domain port → Inventory Application API.
 * Must not import Inventory Domain aggregates or repositories.
 */
final class InventoryStockReservationService implements StockReservationService
{
    public function __construct(
        private OrderStockService $orderStock,
    ) {}

    public function reserveForOrder(string $organizationId, array $lines): void
    {
        try {
            $this->orderStock->reserveForOrder($organizationId, $lines);
        } catch (InsufficientAvailableStockException $e) {
            throw new InsufficientStockForOrder(
                $e->productId,
                $e->requested,
                $e->available,
            );
        } catch (StockItemNotFoundException $e) {
            throw new InsufficientStockForOrder($e->productId, 0, 0);
        }
    }

    public function releaseForOrder(string $organizationId, array $lines): void
    {
        try {
            $this->orderStock->releaseForOrder($organizationId, $lines);
        } catch (StockItemNotFoundException $e) {
            throw new InsufficientStockForOrder($e->productId, 0, 0);
        }
    }

    public function commitForOrder(string $organizationId, array $lines): void
    {
        try {
            $this->orderStock->commitForOrder($organizationId, $lines);
        } catch (StockItemNotFoundException $e) {
            throw new InsufficientStockForOrder($e->productId, 0, 0);
        } catch (InsufficientReservedStockException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    public function restockForOrder(string $organizationId, array $lines): void
    {
        try {
            $this->orderStock->restockForOrder($organizationId, $lines);
        } catch (StockItemNotFoundException $e) {
            throw new InsufficientStockForOrder($e->productId, 0, 0);
        }
    }
}
