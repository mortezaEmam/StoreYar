<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\Enums\ProductStatus;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentProductRepository implements ProductRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::query()->whereKey($id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationAndSku(
        string $organizationId,
        string $sku,
    ): ?Product {
        $model = ProductModel::query()
            ->where('organization_id', $organizationId)
            ->where('sku', $sku)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationId(string $organizationId): array
    {
        return ProductModel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProductModel $model) => $this->toDomain($model))
            ->all();
    }

    public function save(Product $product): void
    {
        $model = ProductModel::query()
            ->whereKey($product->productId()->value())
            ->first();

        if ($model === null) {
            ProductModel::query()->create([
                'id' => $product->productId()->value(),
                'organization_id' => $product->organizationId(),
                'name' => $product->name(),
                'sku' => $product->sku(),
                'status' => $product->status()->value,
                'version' => $product->version(),
                'created_at' => $product->createdAt(),
                'updated_at' => $product->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $product->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing product version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'catalog_products',
            id: $product->productId()->value(),
            expectedVersion: $expectedVersion,
            changes: [
                'organization_id' => $product->organizationId(),
                'name' => $product->name(),
                'sku' => $product->sku(),
                'status' => $product->status()->value,
            ],
        );

        if (! $result->updatedSuccessfully()) {
            throw new ConcurrencyException(
                new ConcurrencyError(
                    code: 'catalog.product.version_conflict',
                    message: 'The product has been modified by another operation.',
                    details: [
                        'product_id' => $product->productId()->value(),
                        'expected_version' => $expectedVersion,
                        'actual_version' => $result->nextVersion(),
                    ],
                ),
            );
        }
    }

    private function toDomain(ProductModel $model): Product
    {
        return Product::reconstitute(
            id: ProductId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            name: $model->name,
            sku: $model->sku,
            status: ProductStatus::from($model->status),
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
