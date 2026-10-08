<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\Aggregates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Enums\ProductStatus;
use StoreYar\Modules\Catalog\Domain\Events\ProductCreated;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;

final class ProductTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-08 12:00:00', new DateTimeZone('UTC'));
    }

    public function test_it_creates_a_draft_product(): void
    {
        $id = ProductId::generate();
        $now = $this->now();

        $product = Product::create(
            id: $id,
            organizationId: '01JORG00000000000000000001',
            name: 'Sample Product',
            sku: 'SKU-001',
            now: $now,
        );

        self::assertSame($id->value(), $product->id());
        self::assertSame('01JORG00000000000000000001', $product->organizationId());
        self::assertSame('Sample Product', $product->name());
        self::assertSame('SKU-001', $product->sku());
        self::assertSame(ProductStatus::DRAFT, $product->status());
        self::assertSame(0, $product->version());
        self::assertSame($now, $product->createdAt());
    }

    public function test_it_records_product_created_event(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: 'Sample Product',
            sku: 'SKU-001',
            now: $this->now(),
        );

        $events = $product->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(ProductCreated::class, $events[0]);
        self::assertSame('Sample Product', $events[0]->name());
        self::assertSame('SKU-001', $events[0]->sku());
        self::assertSame('catalog.product', $events[0]->aggregateType());
    }

    public function test_it_can_activate_and_archive(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: 'Sample Product',
            sku: 'SKU-001',
            now: $this->now(),
        );

        $product->pullDomainEvents();

        $later = $this->now()->modify('+1 hour');
        $product->activate($later);

        self::assertTrue($product->status()->isActive());
        self::assertSame(1, $product->version());

        $product->archive($later->modify('+1 hour'));

        self::assertSame(ProductStatus::ARCHIVED, $product->status());
        self::assertSame(2, $product->version());
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Product::create(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: '   ',
            sku: 'SKU-001',
            now: $this->now(),
        );
    }

    public function test_it_rejects_empty_sku(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Product::create(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: 'Sample Product',
            sku: '',
            now: $this->now(),
        );
    }

    public function test_archived_product_cannot_be_activated(): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            organizationId: '01JORG00000000000000000001',
            name: 'Sample Product',
            sku: 'SKU-001',
            now: $this->now(),
        );

        $now = $this->now();
        $product->archive($now);

        $this->expectException(\LogicException::class);
        $product->activate($now->modify('+1 hour'));
    }
}
