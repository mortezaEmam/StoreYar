<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Aggregates;

use DateTimeImmutable;
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
            throw new \InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self(
            stockItemId: $id,
            organizationId: $organizationId,
            productId: $productId,
            quantity: $quantity,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        StockItemId $id,
        string $organizationId,
        string $productId,
        int $quantity,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $item = new self(
            stockItemId: $id,
            organizationId: $organizationId,
            productId: $productId,
            quantity: $quantity,
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
