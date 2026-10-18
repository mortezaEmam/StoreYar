<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Contracts;

/**
 * Application API for order-driven stock mutations.
 * Owned by Inventory; consumed by Sales via adapter only.
 */
interface OrderStockService
{
    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function reserveForOrder(string $organizationId, array $lines): void;

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function releaseForOrder(string $organizationId, array $lines): void;

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function commitForOrder(string $organizationId, array $lines): void;

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function restockForOrder(string $organizationId, array $lines): void;
}
