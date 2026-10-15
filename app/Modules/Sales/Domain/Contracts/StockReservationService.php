<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

interface StockReservationService
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
