<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Identity\Domain\Aggregates\User;

/** @mixin User */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id(),
            'email' => $user->email(),
            'name' => $user->name(),
            'status' => $user->status()->value,
            'created_at' => $user->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $user->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
