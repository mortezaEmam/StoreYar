<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use StoreYar\Modules\Payments\Domain\Aggregates\Payment;
use StoreYar\Modules\Payments\Domain\Contracts\PaymentRepository;
use StoreYar\Modules\Payments\Domain\Enums\PaymentStatus;
use StoreYar\Modules\Payments\Domain\ValueObjects\PaymentId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;
use StoreYar\Shared\Domain\Contracts\Outbox;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentPaymentRepository implements PaymentRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
        private readonly Outbox $outbox,
    ) {
    }

    public function findById(PaymentId $id): ?Payment
    {
        $model = PaymentModel::query()->whereKey($id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult {
        $base = PaymentModel::query()->where('organization_id', $organizationId);

        $total = (clone $base)->count();

        $models = (clone $base)
            ->orderByDesc('created_at')
            ->offset($page->offset())
            ->limit($page->perPage)
            ->get();

        return new PageResult(
            items: $models->map(fn (PaymentModel $model) => $this->toDomain($model))->all(),
            total: $total,
            page: $page->page,
            perPage: $page->perPage,
        );
    }

    public function save(Payment $payment): void
    {
        $model = PaymentModel::query()
            ->whereKey($payment->paymentId()->value())
            ->first();

        if ($model === null) {
            PaymentModel::query()->create([
                'id' => $payment->paymentId()->value(),
                'organization_id' => $payment->organizationId(),
                'order_id' => $payment->orderId(),
                'amount' => $payment->amount(),
                'currency' => $payment->currency(),
                'status' => $payment->status()->value,
                'version' => $payment->version(),
                'created_at' => $payment->createdAt(),
                'updated_at' => $payment->updatedAt(),
            ]);
        } else {
            $expectedVersion = $payment->version() - 1;

            $result = $this->versionedUpdater->update(
                table: 'payments_payments',
                id: $payment->paymentId()->value(),
                expectedVersion: $expectedVersion,
                changes: [
                    'organization_id' => $payment->organizationId(),
                    'order_id' => $payment->orderId(),
                    'amount' => $payment->amount(),
                    'currency' => $payment->currency(),
                    'status' => $payment->status()->value,
                ],
            );

            if (! $result->updatedSuccessfully()) {
                throw new \RuntimeException('Payment version conflict.');
            }
        }

        $events = $payment->pullDomainEvents();

        if ($events !== []) {
            $this->outbox->recordMany($events);
        }
    }

    private function toDomain(PaymentModel $model): Payment
    {
        return Payment::reconstitute(
            id: PaymentId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            orderId: $model->order_id,
            amount: (int) $model->amount,
            currency: $model->currency,
            status: PaymentStatus::from($model->status),
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
            updatedAt: DateTimeImmutable::createFromInterface($model->updated_at),
            version: (int) $model->version,
        );
    }
}
