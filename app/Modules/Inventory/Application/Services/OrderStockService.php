<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Services;

use StoreYar\Modules\Inventory\Application\Contracts\OrderStockService as OrderStockServiceContract;
use StoreYar\Modules\Inventory\Application\Exceptions\InsufficientAvailableStockException;
use StoreYar\Modules\Inventory\Application\Exceptions\InsufficientReservedStockException;
use StoreYar\Modules\Inventory\Application\Exceptions\StockItemNotFoundException;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientReservedStock;
use StoreYar\Shared\Domain\Contracts\Clock;

final class OrderStockService implements OrderStockServiceContract
{
    public function __construct(
        private StockItemRepository $stockItems,
        private Clock $clock,
    ) {}

    public function reserveForOrder(string $organizationId, array $lines): void
    {
        foreach ($this->sortedLines($lines) as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);

            try {
                $item->reserve($line['quantity'], $this->clock->now());
            } catch (InsufficientAvailableStock) {
                throw new InsufficientAvailableStockException(
                    productId: $line['product_id'],
                    requested: $line['quantity'],
                    available: $item->quantity(),
                );
            }

            $this->stockItems->save($item);
        }
    }

    public function releaseForOrder(string $organizationId, array $lines): void
    {
        foreach ($this->sortedLines($lines) as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);
            $item->release($line['quantity'], $this->clock->now());
            $this->stockItems->save($item);
        }
    }

    public function commitForOrder(string $organizationId, array $lines): void
    {
        foreach ($this->sortedLines($lines) as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);

            try {
                $item->commitReserved($line['quantity'], $this->clock->now());
            } catch (InsufficientReservedStock) {
                throw new InsufficientReservedStockException(
                    productId: $line['product_id'],
                    requested: $line['quantity'],
                    reserved: $item->reservedQuantity(),
                );
            }

            $this->stockItems->save($item);
        }
    }

    public function restockForOrder(string $organizationId, array $lines): void
    {
        foreach ($this->sortedLines($lines) as $line) {
            $item = $this->requireItem($organizationId, $line['product_id']);
            $item->increase($line['quantity'], $this->clock->now());
            $this->stockItems->save($item);
        }
    }

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     * @return list<array{product_id: string, quantity: int}>
     */
    private function sortedLines(array $lines): array
    {
        $sorted = array_values($lines);
        usort(
            $sorted,
            static fn (array $a, array $b): int => strcmp($a['product_id'], $b['product_id']),
        );

        return $sorted;
    }

    private function requireItem(string $organizationId, string $productId): StockItem
    {
        $item = $this->stockItems->findByOrganizationAndProduct(
            $organizationId,
            $productId,
        );

        if ($item === null) {
            throw new StockItemNotFoundException($productId);
        }

        return $item;
    }
}
