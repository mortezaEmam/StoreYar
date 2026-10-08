<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;

/** @mixin Product */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        return [
            'id' => $product->productId()->value(),
            'organization_id' => $product->organizationId(),
            'name' => $product->name(),
            'sku' => $product->sku(),
            'status' => $product->status()->value,
            'created_at' => $product->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
