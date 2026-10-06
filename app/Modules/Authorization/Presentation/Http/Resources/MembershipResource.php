<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;

/** @mixin Membership */
final class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Membership $membership */
        $membership = $this->resource;

        return [
            'id' => $membership->membershipId()->value(),
            'organization_id' => $membership->organizationId(),
            'user_id' => $membership->userId(),
            'role' => $membership->role()->value,
            'created_at' => $membership->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $membership->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
