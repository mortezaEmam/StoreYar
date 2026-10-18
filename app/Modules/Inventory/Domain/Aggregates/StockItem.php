<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Inventory\Domain\Events\StockAdjusted;
use StoreYar\Modules\Inventory\Domain\Events\StockCommitted;
use StoreYar\Modules\Inventory\Domain\Events\StockInitialized;
use StoreYar\Modules\Inventory\Domain\Events\StockReleased;
use StoreYar\Modules\Inventory\Domain\Events\StockReserved;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientReservedStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientStock;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class StockItem extends AggregateRoot
{
    private int $version = 0;

    private function __construct(
        private readonly StockItemId $stockItemId,
        private readonly string $organizationId,
        private readonly string $productId,
        private int $quantity,
        private int $reservedQuantity,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($stockItemId->value());
    }

    public static function create(
        StockItemId $id,
        string $organizationId,
        string $productId,
        int $quantity,
        DateTimeImmutable $now,
    ): self {
        if (trim($organizationId) === '') {
            throw new \InvalidArgumentException('Organization ID is required.');
        }

        if (trim($productId) === '') {
            throw new \InvalidArgumentException('Product ID is required.');
        }

        if ($quantity < 0) {
            throw new \InvalidArgumentException('Initial quantity cannot be negative.');
        }

        $item = new self(
            stockItemId: $id,
            organizationId: $organizationId,
            productId: $productId,
            quantity: $quantity,
            reservedQuantity: 0,
            createdAt: $now,
            updatedAt: $now,
        );

        $item->recordEvent(
            new StockInitialized(
                stockItemId: $item->id(),
                organizationId: $organizationId,
                productId: $productId,
                quantity: $quantity,
                occurredAt: $now,
            ),
        );

        return $item;
    }

    public static function reconstitute(
        StockItemId $id,
        string $organizationId,
        string $productId,
        int $quantity,
        int $reservedQuantity,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $item = new self(
            stockItemId: $id,
            organizationId: $organizationId,
            productId: $productId,
            quantity: $quantity,
            reservedQuantity: $reservedQuantity,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
        $item->version = $version;

        return $item;
    }

    public function increase(int $amount, DateTimeImmutable $now): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Increase amount must be positive.');
        }

        $this->quantity += $amount;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new StockAdjusted(
                stockItemId: $this->id(),
                organizationId: $this->organizationId,
                productId: $this->productId,
                direction: 'increase',
                amount: $amount,
                quantityAfter: $this->quantity,
                occurredAt: $now,
            ),
        );
    }

    public function decrease(int $amount, DateTimeImmutable $now): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Decrease amount must be positive.');
        }

        if ($this->quantity < $amount) {
            throw new InsufficientStock($this->quantity, $amount);
        }

        $this->quantity -= $amount;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new StockAdjusted(
                stockItemId: $this->id(),
                organizationId: $this->organizationId,
                productId: $this->productId,
                direction: 'decrease',
                amount: $amount,
                quantityAfter: $this->quantity,
                occurredAt: $now,
            ),
        );
    }

    public function reserve(int $amount, DateTimeImmutable $now): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Reserve amount must be positive.');
        }

        if ($this->quantity < $amount) {
            throw new InsufficientAvailableStock($this->quantity, $amount);
        }

        $this->quantity -= $amount;
        $this->reservedQuantity += $amount;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new StockReserved(
                stockItemId: $this->id(),
                organizationId: $this->organizationId,
                productId: $this->productId,
                amount: $amount,
                quantityAfter: $this->quantity,
                reservedQuantityAfter: $this->reservedQuantity,
                occurredAt: $now,
            ),
        );
    }

    public function release(int $amount, DateTimeImmutable $now): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Release amount must be positive.');
        }

        if ($this->reservedQuantity < $amount) {
            throw new InsufficientReservedStock($this->reservedQuantity, $amount);
        }

        $this->reservedQuantity -= $amount;
        $this->quantity += $amount;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new StockReleased(
                stockItemId: $this->id(),
                organizationId: $this->organizationId,
                productId: $this->productId,
                amount: $amount,
                quantityAfter: $this->quantity,
                reservedQuantityAfter: $this->reservedQuantity,
                occurredAt: $now,
            ),
        );
    }

    public function commitReserved(int $amount, DateTimeImmutable $now): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Commit amount must be positive.');
        }

        if ($this->reservedQuantity < $amount) {
            throw new InsufficientReservedStock($this->reservedQuantity, $amount);
        }

        $this->reservedQuantity -= $amount;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new StockCommitted(
                stockItemId: $this->id(),
                organizationId: $this->organizationId,
                productId: $this->productId,
                amount: $amount,
                quantityAfter: $this->quantity,
                reservedQuantityAfter: $this->reservedQuantity,
                occurredAt: $now,
            ),
        );
    }

    public function stockItemId(): StockItemId
    {
        return $this->stockItemId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function reservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
