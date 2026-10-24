<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;

/** @mixin Product */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        $categoryId = null;
        try {
            $categoryId = DB::table('catalog_products')
                ->where('id', $product->productId()->value())
                ->value('category_id');
        } catch (\Throwable) {
            $categoryId = null;
        }

        return [
            'id' => $product->productId()->value(),
            'organization_id' => $product->organizationId(),
            'category_id' => $categoryId,
            'name' => $product->name(),
            'sku' => $product->sku(),
            'unit_price_amount' => $product->unitPriceAmount(),
            'currency' => $product->currency(),
            'status' => $product->status()->value,
            'created_at' => $product->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
