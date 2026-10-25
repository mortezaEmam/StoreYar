<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel */
final class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'address' => $this->address,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
