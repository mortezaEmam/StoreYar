<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Sales\Domain\Entities\OrderLine;
use StoreYar\Modules\Sales\Domain\Enums\OrderStatus;
use StoreYar\Modules\Sales\Domain\Events\OrderConfirmed;
use StoreYar\Modules\Sales\Domain\Events\OrderCreated;
use StoreYar\Modules\Sales\Domain\Events\OrderLineAdded;
use StoreYar\Modules\Sales\Domain\Exceptions\DuplicateOrderLine;
use StoreYar\Modules\Sales\Domain\Exceptions\EmptyOrder;
use StoreYar\Modules\Sales\Domain\Exceptions\InvalidOrderState;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderLineId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var list<OrderLine> */
    private array $lines = [];

    private OrderStatus $status;

    private int $version = 0;

    private function __construct(
        private readonly OrderId $orderId,
        private readonly string $organizationId,
        private readonly string $customerUserId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        OrderStatus $status,
    ) {
        parent::__construct($orderId->value());
        $this->status = $status;
    }

    public static function create(
        OrderId $id,
        string $organizationId,
        string $customerUserId,
        DateTimeImmutable $now,
    ): self {
        if (trim($organizationId) === '') {
            throw new \InvalidArgumentException('Organization ID is required.');
        }

        if (trim($customerUserId) === '') {
            throw new \InvalidArgumentException('Customer user ID is required.');
        }

        $order = new self(
            orderId: $id,
            organizationId: $organizationId,
            customerUserId: $customerUserId,
            createdAt: $now,
            updatedAt: $now,
            status: OrderStatus::DRAFT,
        );

        $order->recordEvent(
            new OrderCreated(
                orderId: $order->id(),
                organizationId: $organizationId,
                customerUserId: $customerUserId,
                occurredAt: $now,
            ),
        );

        return $order;
    }

    /**
     * @param list<OrderLine> $lines
     */
    public static function reconstitute(
        OrderId $id,
        string $organizationId,
        string $customerUserId,
        OrderStatus $status,
        array $lines,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $order = new self(
            orderId: $id,
            organizationId: $organizationId,
            customerUserId: $customerUserId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            status: $status,
        );

        $order->lines = array_values($lines);
        $order->version = $version;

        return $order;
    }

    public function addLine(
        string $productId,
        int $quantity,
        Money $unitPrice,
        DateTimeImmutable $now,
    ): OrderLine {
        if (! $this->status->canAddLine()) {
            throw new InvalidOrderState('add line to', $this->status);
        }

        foreach ($this->lines as $existing) {
            if ($existing->productId() === $productId) {
                throw new DuplicateOrderLine($productId);
            }
        }

        $line = OrderLine::create(
            id: OrderLineId::generate(),
            productId: $productId,
            quantity: $quantity,
            unitPrice: $unitPrice,
        );

        $this->lines[] = $line;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new OrderLineAdded(
                orderId: $this->id(),
                productId: $productId,
                quantity: $quantity,
                unitPriceAmount: $unitPrice->amount(),
                currency: $unitPrice->currency(),
                occurredAt: $now,
            ),
        );

        return $line;
    }

    /**
     * Transitions order to confirmed.
     * Stock consumption is orchestrated by the application layer BEFORE or
     * in the same transaction as save — not inside this method.
     */
    public function confirm(DateTimeImmutable $now): void
    {
        if (! $this->status->canConfirm()) {
            throw new InvalidOrderState('confirm', $this->status);
        }

        if ($this->lines === []) {
            throw new EmptyOrder();
        }

        $this->status = OrderStatus::CONFIRMED;
        $this->updatedAt = $now;
        $this->version++;

        $linePayload = [];
        foreach ($this->lines as $line) {
            $linePayload[] = [
                'product_id' => $line->productId(),
                'quantity' => $line->quantity(),
            ];
        }

        $total = $this->total();

        $this->recordEvent(
            new OrderConfirmed(
                orderId: $this->id(),
                organizationId: $this->organizationId,
                lines: $linePayload,
                totalAmount: $total->amount(),
                currency: $total->currency(),
                occurredAt: $now,
            ),
        );
    }

    public function cancel(DateTimeImmutable $now): void
    {
        if (! $this->status->canCancel()) {
            throw new InvalidOrderState('cancel', $this->status);
        }

        // Restock for confirmed orders is application-layer responsibility.
        $this->status = OrderStatus::CANCELLED;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function total(): Money
    {
        if ($this->lines === []) {
            return Money::of(0);
        }

        $total = Money::of(0, $this->lines[0]->unitPrice()->currency());

        foreach ($this->lines as $line) {
            $total = $total->add($line->lineTotal());
        }

        return $total;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function customerUserId(): string
    {
        return $this->customerUserId;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return list<OrderLine>
     */
    public function lines(): array
    {
        return $this->lines;
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
