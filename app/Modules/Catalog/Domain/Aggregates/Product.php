<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use StoreYar\Modules\Catalog\Domain\Enums\ProductStatus;
use StoreYar\Modules\Catalog\Domain\Events\ProductCreated;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class Product extends AggregateRoot
{
    private ProductStatus $status;

    private int $version = 0;

    private int $unitPriceAmount;

    private string $currency;

    private function __construct(
        private readonly ProductId $productId,
        private readonly string $organizationId,
        private string $name,
        private string $sku,
        int $unitPriceAmount,
        string $currency,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        ProductStatus $status,
    ) {
        parent::__construct($productId->value());

        $this->status = $status;
        $this->unitPriceAmount = $unitPriceAmount;
        $this->currency = strtoupper($currency);
    }

    public static function create(
        ProductId $id,
        string $organizationId,
        string $name,
        string $sku,
        int $unitPriceAmount,
        DateTimeImmutable $now,
        string $currency = 'IRR',
    ): self {
        self::assertRequired($organizationId, 'Organization ID');
        self::assertRequired($name, 'Product name');
        self::assertRequired($sku, 'Product SKU');

        self::assertValidPrice($unitPriceAmount);
        $currency = self::normalizeCurrency($currency);

        $product = new self(
            productId: $id,
            organizationId: $organizationId,
            name: $name,
            sku: $sku,
            unitPriceAmount: $unitPriceAmount,
            currency: $currency,
            createdAt: $now,
            updatedAt: $now,
            status: ProductStatus::DRAFT,
        );

        $product->recordEvent(
            new ProductCreated(
                productId: $product->id(),
                organizationId: $organizationId,
                name: $name,
                sku: $sku,
                occurredAt: $now,
            ),
        );

        return $product;
    }

    public static function reconstitute(
        ProductId $id,
        string $organizationId,
        string $name,
        string $sku,
        int $unitPriceAmount,
        string $currency,
        ProductStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        self::assertRequired($organizationId, 'Organization ID');
        self::assertRequired($name, 'Product name');
        self::assertRequired($sku, 'Product SKU');

        self::assertValidPrice($unitPriceAmount);
        $currency = self::normalizeCurrency($currency);

        $product = new self(
            productId: $id,
            organizationId: $organizationId,
            name: $name,
            sku: $sku,
            unitPriceAmount: $unitPriceAmount,
            currency: $currency,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            status: $status,
        );

        $product->version = $version;

        return $product;
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        self::assertRequired($name, 'Product name');

        $this->name = $name;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status === ProductStatus::ARCHIVED) {
            throw new LogicException(
                'Archived product cannot be activated.'
            );
        }

        $this->status = ProductStatus::ACTIVE;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function archive(DateTimeImmutable $now): void
    {
        $this->status = ProductStatus::ARCHIVED;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function changePrice(
        int $unitPriceAmount,
        string $currency,
        DateTimeImmutable $now,
    ): void {
        self::assertValidPrice($unitPriceAmount);

        $currency = self::normalizeCurrency($currency);

        $this->unitPriceAmount = $unitPriceAmount;
        $this->currency = $currency;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function status(): ProductStatus
    {
        return $this->status;
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

    public function unitPriceAmount(): int
    {
        return $this->unitPriceAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    private static function assertRequired(
        string $value,
        string $field,
    ): void {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                $field . ' is required.'
            );
        }
    }

    private static function assertValidPrice(int $unitPriceAmount): void
    {
        if ($unitPriceAmount < 0) {
            throw new InvalidArgumentException(
                'Unit price cannot be negative.'
            );
        }
    }

    private static function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if ($currency === '') {
            throw new InvalidArgumentException(
                'Currency is required.'
            );
        }

        return $currency;
    }
}
