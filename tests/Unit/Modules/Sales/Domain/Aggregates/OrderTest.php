<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sales\Domain\Aggregates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Enums\OrderStatus;
use StoreYar\Modules\Sales\Domain\Events\OrderConfirmed;
use StoreYar\Modules\Sales\Domain\Events\OrderCreated;
use StoreYar\Modules\Sales\Domain\Events\OrderLineAdded;
use StoreYar\Modules\Sales\Domain\Exceptions\DuplicateOrderLine;
use StoreYar\Modules\Sales\Domain\Exceptions\EmptyOrder;
use StoreYar\Modules\Sales\Domain\Exceptions\InvalidOrderState;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;

final class OrderTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-10 12:00:00', new DateTimeZone('UTC'));
    }

    private function newDraftOrder(): Order
    {
        return Order::create(
            id: OrderId::generate(),
            organizationId: '01JORG00000000000000000001',
            customerUserId: '01JUSER0000000000000000001',
            now: $this->now(),
        );
    }

    public function test_it_creates_draft_order_and_records_event(): void
    {
        $order = $this->newDraftOrder();

        self::assertTrue($order->status()->isDraft());
        self::assertSame(0, $order->version());

        $events = $order->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCreated::class, $events[0]);
    }

    public function test_it_adds_line_and_computes_total(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();

        $order->addLine(
            productId: '01JPROD0000000000000000001',
            quantity: 2,
            unitPrice: Money::of(5000),
            now: $this->now()->modify('+1 minute'),
        );

        self::assertCount(1, $order->lines());
        self::assertSame(10000, $order->total()->amount());
        self::assertSame(1, $order->version());

        $events = $order->pullDomainEvents();
        self::assertInstanceOf(OrderLineAdded::class, $events[0]);
    }

    public function test_it_rejects_duplicate_product_line(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();

        $order->addLine('01JPROD0000000000000000001', 1, Money::of(1000), $this->now());

        $this->expectException(DuplicateOrderLine::class);
        $order->addLine('01JPROD0000000000000000001', 2, Money::of(1000), $this->now());
    }

    public function test_it_rejects_confirm_without_lines(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();

        $this->expectException(EmptyOrder::class);
        $order->confirm($this->now());
    }

    public function test_it_confirms_order_with_lines(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();

        $order->addLine('01JPROD0000000000000000001', 3, Money::of(2000), $this->now());
        $order->pullDomainEvents();

        $order->confirm($this->now()->modify('+1 hour'));

        self::assertTrue($order->status()->isConfirmed());

        $events = $order->pullDomainEvents();
        self::assertInstanceOf(OrderConfirmed::class, $events[0]);
        self::assertSame(6000, $events[0]->totalAmount());
    }

    public function test_it_rejects_add_line_when_confirmed(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();
        $order->addLine('01JPROD0000000000000000001', 1, Money::of(1000), $this->now());
        $order->confirm($this->now());

        $this->expectException(InvalidOrderState::class);
        $order->addLine('01JPROD0000000000000000002', 1, Money::of(1000), $this->now());
    }

    public function test_it_cancels_draft_order(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();

        $order->cancel($this->now());

        self::assertSame(OrderStatus::CANCELLED, $order->status());
    }

    public function test_it_rejects_confirm_when_cancelled(): void
    {
        $order = $this->newDraftOrder();
        $order->pullDomainEvents();
        $order->cancel($this->now());

        $this->expectException(InvalidOrderState::class);
        $order->confirm($this->now());
    }
}
