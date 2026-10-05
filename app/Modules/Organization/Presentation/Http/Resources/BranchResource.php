<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Organization\Domain\Entities\Branch;

/** @mixin Branch */
final class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Branch $branch */
        $branch = $this->resource;

        return [
            'id' => $branch->branchId()->value(),
            'organization_id' => $branch->organizationId()->value(),
            'name' => $branch->name(),
            'status' => $branch->status()->value,
            'created_at' => $branch->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $branch->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
