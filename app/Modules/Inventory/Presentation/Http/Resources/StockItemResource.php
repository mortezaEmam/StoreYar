<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;

/** @mixin StockItem */
final class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var StockItem $item */
        $item = $this->resource;

        return [
            'id' => $item->stockItemId()->value(),
            'organization_id' => $item->organizationId(),
            'product_id' => $item->productId(),
            'quantity' => $item->quantity(),
            'created_at' => $item->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $item->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
