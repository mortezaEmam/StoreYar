<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentStockItemRepository implements StockItemRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(StockItemId $id): ?StockItem
    {
        $model = StockItemModel::query()->whereKey($id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationAndProduct(
        string $organizationId,
        string $productId,
    ): ?StockItem {
        $model = StockItemModel::query()
            ->where('organization_id', $organizationId)
            ->where('product_id', $productId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationId(string $organizationId): array
    {
        return StockItemModel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (StockItemModel $model) => $this->toDomain($model))
            ->all();
    }

    public function save(StockItem $stockItem): void
    {
        $model = StockItemModel::query()
            ->whereKey($stockItem->stockItemId()->value())
            ->first();

        if ($model === null) {
            StockItemModel::query()->create([
                'id' => $stockItem->stockItemId()->value(),
                'organization_id' => $stockItem->organizationId(),
                'product_id' => $stockItem->productId(),
                'quantity' => $stockItem->quantity(),
                'version' => $stockItem->version(),
                'created_at' => $stockItem->createdAt(),
                'updated_at' => $stockItem->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $stockItem->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing stock item version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'inventory_stock_items',
            id: $stockItem->stockItemId()->value(),
            expectedVersion: $expectedVersion,
            changes: [
                'organization_id' => $stockItem->organizationId(),
                'product_id' => $stockItem->productId(),
                'quantity' => $stockItem->quantity(),
            ],
        );

        if (! $result->updatedSuccessfully()) {
            throw new ConcurrencyException(
                new ConcurrencyError(
                    code: 'inventory.stock_item.version_conflict',
                    message: 'The stock item has been modified by another operation.',
                    details: [
                        'stock_item_id' => $stockItem->stockItemId()->value(),
                        'expected_version' => $expectedVersion,
                        'actual_version' => $result->nextVersion(),
                    ],
                ),
            );
        }
    }

    private function toDomain(StockItemModel $model): StockItem
    {
        return StockItem::reconstitute(
            id: StockItemId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            productId: $model->product_id,
            quantity: (int) $model->quantity,
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
