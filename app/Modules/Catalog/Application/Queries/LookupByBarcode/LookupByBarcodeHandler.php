<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\LookupByBarcode;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductBarcodeModel;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductModel;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class LookupByBarcodeHandler implements QueryHandler
{
    public function handle(Query $query): ?array
    {
        if (! $query instanceof LookupByBarcodeQuery) {
            throw new \InvalidArgumentException('Invalid query.');
        }

        $row = ProductBarcodeModel::query()
            ->where('organization_id', $query->organizationId)
            ->where('barcode', trim($query->barcode))
            ->first();

        if ($row === null) {
            return null;
        }

        $product = ProductModel::query()
            ->where('id', $row->product_id)
            ->where('organization_id', $query->organizationId)
            ->first();

        if ($product === null) {
            return null;
        }

        return [
            'barcode' => [
                'id' => $row->id,
                'barcode' => $row->barcode,
                'type' => $row->type,
                'is_primary' => (bool) $row->is_primary,
                'unit_id' => $row->unit_id,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price_amount' => $product->unit_price_amount,
                'currency' => $product->currency,
                'status' => $product->status,
                'is_sellable' => (bool) ($product->is_sellable ?? true),
                'is_stockable' => (bool) ($product->is_stockable ?? true),
            ],
        ];
    }
}
