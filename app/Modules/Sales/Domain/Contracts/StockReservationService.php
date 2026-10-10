<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

interface StockReservationService
{
    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function consumeForOrder(string $organizationId, array $lines): void;

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function restoreForOrder(string $organizationId, array $lines): void;
}
