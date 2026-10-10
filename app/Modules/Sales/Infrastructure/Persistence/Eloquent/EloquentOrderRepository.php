<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\Entities\OrderLine;
use StoreYar\Modules\Sales\Domain\Enums\OrderStatus;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderLineId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
        private readonly \StoreYar\Shared\Domain\Contracts\Outbox $outbox,
    ) {
    }

    public function findById(OrderId $id): ?Order
    {
        $model = OrderModel::query()
            ->with('lines')
            ->whereKey($id->value())
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationId(string $organizationId): array
    {
        return OrderModel::query()
            ->with('lines')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OrderModel $model) => $this->toDomain($model))
            ->all();
    }

    public function save(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $model = OrderModel::query()
                ->whereKey($order->orderId()->value())
                ->first();

            if ($model === null) {
                OrderModel::query()->create([
                    'id' => $order->orderId()->value(),
                    'organization_id' => $order->organizationId(),
                    'customer_user_id' => $order->customerUserId(),
                    'status' => $order->status()->value,
                    'version' => $order->version(),
                    'created_at' => $order->createdAt(),
                    'updated_at' => $order->updatedAt(),
                ]);
            } else {
                $expectedVersion = $order->version() - 1;

                if ($expectedVersion < 0) {
                    throw new \LogicException(
                        'Existing order version must be greater than zero.',
                    );
                }

                $result = $this->versionedUpdater->update(
                    table: 'sales_orders',
                    id: $order->orderId()->value(),
                    expectedVersion: $expectedVersion,
                    changes: [
                        'organization_id' => $order->organizationId(),
                        'customer_user_id' => $order->customerUserId(),
                        'status' => $order->status()->value,
                    ],
                );

                if (! $result->updatedSuccessfully()) {
                    throw new ConcurrencyException(
                        new ConcurrencyError(
                            code: 'sales.order.version_conflict',
                            message: 'The order has been modified by another operation.',
                            details: [
                                'order_id' => $order->orderId()->value(),
                                'expected_version' => $expectedVersion,
                                'actual_version' => $result->nextVersion(),
                            ],
                        ),
                    );
                }
            }

            OrderLineModel::query()
                ->where('order_id', $order->orderId()->value())
                ->delete();

            foreach ($order->lines() as $line) {
                OrderLineModel::query()->create([
                    'id' => $line->orderLineId()->value(),
                    'order_id' => $order->orderId()->value(),
                    'product_id' => $line->productId(),
                    'quantity' => $line->quantity(),
                    'unit_price_amount' => $line->unitPrice()->amount(),
                    'currency' => $line->unitPrice()->currency(),
                    'created_at' => $order->updatedAt(),
                    'updated_at' => $order->updatedAt(),
                ]);
            }


            $events = $order->pullDomainEvents();

            if ($events !== []) {
                $this->outbox->recordMany($events);
            }
        });
    }

    private function toDomain(OrderModel $model): Order
    {
        $lines = [];

        foreach ($model->lines as $lineModel) {
            $lines[] = OrderLine::reconstitute(
                id: OrderLineId::fromString($lineModel->getKey()),
                productId: $lineModel->product_id,
                quantity: (int) $lineModel->quantity,
                unitPrice: Money::of(
                    (int) $lineModel->unit_price_amount,
                    $lineModel->currency,
                ),
            );
        }

        return Order::reconstitute(
            id: OrderId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            customerUserId: $model->customer_user_id,
            status: OrderStatus::from($model->status),
            lines: $lines,
            createdAt: $model->created_at instanceof \DateTimeImmutable
                ? $model->created_at
                : \DateTimeImmutable::createFromInterface($model->created_at),
            updatedAt: $model->updated_at instanceof \DateTimeImmutable
                ? $model->updated_at
                : \DateTimeImmutable::createFromInterface($model->updated_at),
            version: (int) $model->version,
        );
    }
}
