<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Organization\Domain\Aggregates\Organization;

/** @mixin Organization */
final class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Organization $organization */
        $organization = $this->resource;

        return [
            'id' => $organization->organizationId()->value(),
            'name' => $organization->name(),
            'owner_user_id' => $organization->ownerUserId(),
            'status' => $organization->status()->value,
            'created_at' => $organization->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $organization->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
