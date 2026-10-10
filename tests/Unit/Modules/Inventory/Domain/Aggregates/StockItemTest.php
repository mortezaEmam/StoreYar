<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory\Domain\Aggregates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientStock;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;

final class StockItemTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-10 12:00:00', new DateTimeZone('UTC'));
    }

    public function test_it_creates_stock_item(): void
    {
        $id = StockItemId::generate();
        $now = $this->now();

        $item = StockItem::create(
            id: $id,
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: 10,
            now: $now,
        );

        self::assertSame($id->value(), $item->id());
        self::assertSame(10, $item->quantity());
        self::assertSame(0, $item->version());
    }

    public function test_it_increases_and_decreases(): void
    {
        $item = StockItem::create(
            id: StockItemId::generate(),
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: 10,
            now: $this->now(),
        );

        $later = $this->now()->modify('+1 hour');
        $item->increase(5, $later);
        self::assertSame(15, $item->quantity());
        self::assertSame(1, $item->version());

        $item->decrease(3, $later->modify('+1 hour'));
        self::assertSame(12, $item->quantity());
        self::assertSame(2, $item->version());
    }

    public function test_it_rejects_insufficient_stock(): void
    {
        $item = StockItem::create(
            id: StockItemId::generate(),
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: 2,
            now: $this->now(),
        );

        $this->expectException(InsufficientStock::class);
        $item->decrease(5, $this->now());
    }

    public function test_it_rejects_negative_initial_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StockItem::create(
            id: StockItemId::generate(),
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: -1,
            now: $this->now(),
        );
    }
}
