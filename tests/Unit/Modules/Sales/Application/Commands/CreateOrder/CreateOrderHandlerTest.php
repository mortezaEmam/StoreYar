<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sales\Application\Commands\CreateOrder;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Sales\Application\Commands\CreateOrder\CreateOrderCommand;
use StoreYar\Modules\Sales\Application\Commands\CreateOrder\CreateOrderHandler;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateOrderHandlerTest extends TestCase
{
    public function test_it_creates_and_saves_order(): void
    {
        $now = new DateTimeImmutable('2026-10-10 12:00:00', new DateTimeZone('UTC'));

        $repository = $this->createMock(OrderRepository::class);
        $repository->expects($this->once())->method('save');

        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($now);

        $handler = new CreateOrderHandler($repository, $clock);

        $order = $handler->handle(
            new CreateOrderCommand(
                organizationId: '01JORG00000000000000000001',
                customerUserId: '01JUSER0000000000000000001',
            ),
        );

        self::assertInstanceOf(Order::class, $order);
        self::assertTrue($order->status()->isDraft());
        self::assertSame('01JORG00000000000000000001', $order->organizationId());
    }
}
