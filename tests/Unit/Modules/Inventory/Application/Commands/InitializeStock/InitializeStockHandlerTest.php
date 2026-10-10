<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory\Application\Commands\InitializeStock;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Inventory\Application\Commands\InitializeStock\InitializeStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\InitializeStock\InitializeStockHandler;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\ProductExistenceChecker;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\Exceptions\StockItemAlreadyExists;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class InitializeStockHandlerTest extends TestCase
{
    public function test_it_initializes_stock(): void
    {
        $now = new DateTimeImmutable(
            '2026-10-10 12:00:00',
            new DateTimeZone('UTC'),
        );

        $repository = $this->createMock(StockItemRepository::class);

        $repository
            ->method('findByOrganizationAndProduct')
            ->willReturn(null);

        $repository
            ->expects($this->once())
            ->method('save');

        $products = $this->createMock(ProductExistenceChecker::class);

        $products
            ->expects($this->once())
            ->method('existsInOrganization')
            ->with(
                '01JORG00000000000000000001',
                '01JPROD0000000000000000001',
            )
            ->willReturn(true);

        $clock = $this->createMock(Clock::class);

        $clock
            ->method('now')
            ->willReturn($now);

        $handler = new InitializeStockHandler(
            $repository,
            $products,
            $clock,
        );

        $item = $handler->handle(
            new InitializeStockCommand(
                organizationId: '01JORG00000000000000000001',
                productId: '01JPROD0000000000000000001',
                quantity: 5,
            ),
        );

        self::assertInstanceOf(StockItem::class, $item);
        self::assertSame(5, $item->quantity());
    }

    public function test_it_rejects_duplicate(): void
    {
        $existing = StockItem::reconstitute(
            id: StockItemId::generate(),
            organizationId: '01JORG00000000000000000001',
            productId: '01JPROD0000000000000000001',
            quantity: 1,
            createdAt: new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC'),
            ),
            updatedAt: new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC'),
            ),
            version: 0,
        );

        $repository = $this->createMock(StockItemRepository::class);

        $repository
            ->method('findByOrganizationAndProduct')
            ->willReturn($existing);

        $repository
            ->expects($this->never())
            ->method('save');

        $products = $this->createMock(ProductExistenceChecker::class);

        $products
            ->expects($this->once())
            ->method('existsInOrganization')
            ->with(
                '01JORG00000000000000000001',
                '01JPROD0000000000000000001',
            )
            ->willReturn(true);

        $clock = $this->createMock(Clock::class);

        $handler = new InitializeStockHandler(
            $repository,
            $products,
            $clock,
        );

        $this->expectException(StockItemAlreadyExists::class);

        $handler->handle(
            new InitializeStockCommand(
                organizationId: '01JORG00000000000000000001',
                productId: '01JPROD0000000000000000001',
                quantity: 0,
            ),
        );
    }
}
