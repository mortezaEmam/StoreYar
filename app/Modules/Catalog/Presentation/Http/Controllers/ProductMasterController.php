<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\UpdateProductMaster\UpdateProductMasterCommand;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class ProductMasterController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function show(string $id): JsonResponse
    {
        $p = ProductModel::query()
            ->where('id', $id)
            ->where('organization_id', $this->business->businessId())
            ->first();

        if ($p === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $this->toArray($p)]);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'short_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'brand_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'category_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'base_unit_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'sale_unit_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'purchase_unit_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'cost_price_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'is_stockable' => ['sometimes', 'boolean'],
            'is_taxable' => ['sometimes', 'boolean'],
            'min_stock' => ['sometimes', 'nullable', 'numeric'],
            'max_stock' => ['sometimes', 'nullable', 'numeric'],
            'reorder_point' => ['sometimes', 'nullable', 'numeric'],
            'reorder_qty' => ['sometimes', 'nullable', 'numeric'],
            'track_lot' => ['sometimes', 'boolean'],
            'track_serial' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
        ]);

        $product = $this->commands->dispatch(
            new UpdateProductMasterCommand(
                organizationId: $this->business->businessId(),
                productId: $id,
                shortName: $data['short_name'] ?? null,
                description: $data['description'] ?? null,
                brandId: $data['brand_id'] ?? null,
                categoryId: $data['category_id'] ?? null,
                baseUnitId: $data['base_unit_id'] ?? null,
                saleUnitId: $data['sale_unit_id'] ?? null,
                purchaseUnitId: $data['purchase_unit_id'] ?? null,
                costPriceAmount: array_key_exists('cost_price_amount', $data) ? $data['cost_price_amount'] : null,
                isSellable: array_key_exists('is_sellable', $data) ? (bool) $data['is_sellable'] : null,
                isPurchasable: array_key_exists('is_purchasable', $data) ? (bool) $data['is_purchasable'] : null,
                isStockable: array_key_exists('is_stockable', $data) ? (bool) $data['is_stockable'] : null,
                isTaxable: array_key_exists('is_taxable', $data) ? (bool) $data['is_taxable'] : null,
                minStock: isset($data['min_stock']) ? (float) $data['min_stock'] : null,
                maxStock: isset($data['max_stock']) ? (float) $data['max_stock'] : null,
                reorderPoint: isset($data['reorder_point']) ? (float) $data['reorder_point'] : null,
                reorderQty: isset($data['reorder_qty']) ? (float) $data['reorder_qty'] : null,
                trackLot: array_key_exists('track_lot', $data) ? (bool) $data['track_lot'] : null,
                trackSerial: array_key_exists('track_serial', $data) ? (bool) $data['track_serial'] : null,
                trackExpiry: array_key_exists('track_expiry', $data) ? (bool) $data['track_expiry'] : null,
            ),
        );

        return response()->json(['data' => $this->toArray($product)]);
    }

    private function toArray(ProductModel $p): array
    {
        return [
            'id' => $p->id,
            'organization_id' => $p->organization_id,
            'name' => $p->name,
            'short_name' => $p->short_name,
            'description' => $p->description,
            'sku' => $p->sku,
            'status' => $p->status,
            'category_id' => $p->category_id,
            'brand_id' => $p->brand_id,
            'product_type' => $p->product_type,
            'base_unit_id' => $p->base_unit_id,
            'sale_unit_id' => $p->sale_unit_id,
            'purchase_unit_id' => $p->purchase_unit_id,
            'unit_price_amount' => $p->unit_price_amount,
            'cost_price_amount' => $p->cost_price_amount,
            'currency' => $p->currency,
            'is_sellable' => (bool) ($p->is_sellable ?? true),
            'is_purchasable' => (bool) ($p->is_purchasable ?? true),
            'is_stockable' => (bool) ($p->is_stockable ?? true),
            'is_taxable' => (bool) ($p->is_taxable ?? true),
            'min_stock' => $p->min_stock,
            'max_stock' => $p->max_stock,
            'reorder_point' => $p->reorder_point,
            'reorder_qty' => $p->reorder_qty,
            'track_lot' => (bool) ($p->track_lot ?? false),
            'track_serial' => (bool) ($p->track_serial ?? false),
            'track_expiry' => (bool) ($p->track_expiry ?? false),
            'version' => $p->version,
        ];
    }
}
