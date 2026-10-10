<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sales\Application\Commands\ConfirmOrder;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\ConfirmOrder\ConfirmOrderHandler;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Domain\Exceptions\InsufficientStockForOrder;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ConfirmOrderHandlerTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-10 12:00:00', new DateTimeZone('UTC'));
    }

    private function orderWithLine(): Order
    {
        $order = Order::create(
            id: OrderId::generate(),
            organizationId: '01JORG00000000000000000001',
            customerUserId: '01JUSER0000000000000000001',
            now: $this->now(),
        );
        $order->pullDomainEvents();
        $order->addLine(
            '01JPROD0000000000000000001',
            2,
            Money::of(1000),
            $this->now(),
        );
        $order->pullDomainEvents();

        return $order;
    }

    private function databaseManager(): DatabaseManager
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('transaction')->willReturnCallback(
            static fn (callable $callback) => $callback(),
        );

        $db = $this->createMock(DatabaseManager::class);
        $db->method('connection')->willReturn($connection);

        return $db;
    }

    public function test_it_consumes_stock_and_confirms(): void
    {
        $order = $this->orderWithLine();

        $repository = $this->createMock(OrderRepository::class);
        $repository->method('findById')->willReturn($order);
        $repository->expects($this->once())->method('save');

        $stock = $this->createMock(StockReservationService::class);
        $stock->expects($this->once())->method('consumeForOrder');

        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($this->now());

        $handler = new ConfirmOrderHandler(
            $repository,
            $stock,
            $clock,
            $this->databaseManager(),
        );

        $result = $handler->handle(
            new ConfirmOrderCommand(
                orderId: $order->orderId()->value(),
                organizationId: '01JORG00000000000000000001',
            ),
        );

        self::assertTrue($result->status()->isConfirmed());
    }

    public function test_it_does_not_confirm_when_stock_fails(): void
    {
        $order = $this->orderWithLine();

        $repository = $this->createMock(OrderRepository::class);
        $repository->method('findById')->willReturn($order);
        $repository->expects($this->never())->method('save');

        $stock = $this->createMock(StockReservationService::class);
        $stock->method('consumeForOrder')->willThrowException(
            new InsufficientStockForOrder('01JPROD0000000000000000001', 2, 0),
        );

        $handler = new ConfirmOrderHandler(
            $repository,
            $stock,
            $this->createMock(Clock::class),
            $this->databaseManager(),
        );

        $this->expectException(InsufficientStockForOrder::class);

        $handler->handle(
            new ConfirmOrderCommand(
                orderId: $order->orderId()->value(),
                organizationId: '01JORG00000000000000000001',
            ),
        );
    }
}
