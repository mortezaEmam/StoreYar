<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Entities;

use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderLineId;

final class OrderLine
{
    private function __construct(
        private readonly OrderLineId $orderLineId,
        private readonly string $productId,
        private int $quantity,
        private readonly Money $unitPrice,
    ) {
    }

    public static function create(
        OrderLineId $id,
        string $productId,
        int $quantity,
        Money $unitPrice,
    ): self {
        if (trim($productId) === '') {
            throw new \InvalidArgumentException('Product ID is required.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('Line quantity must be at least 1.');
        }

        return new self(
            orderLineId: $id,
            productId: $productId,
            quantity: $quantity,
            unitPrice: $unitPrice,
        );
    }

    public static function reconstitute(
        OrderLineId $id,
        string $productId,
        int $quantity,
        Money $unitPrice,
    ): self {
        return new self(
            orderLineId: $id,
            productId: $productId,
            quantity: $quantity,
            unitPrice: $unitPrice,
        );
    }

    public function changeQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Line quantity must be at least 1.');
        }

        $this->quantity = $quantity;
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    public function orderLineId(): OrderLineId
    {
        return $this->orderLineId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }
}
