<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Application\Results\SessionRotationResult;

final class AuthenticationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = $this->resource;

        if ($result instanceof AuthenticationResult) {
            return [
                'token' => $result->token,
                'session_id' => $result->session->sessionId()->value(),
                'expires_at' => $result->session->expiresAt()->format(\DateTimeInterface::ATOM),
                'user' => new UserResource($result->user),
            ];
        }

        if ($result instanceof SessionRotationResult) {
            return [
                'token' => $result->token,
                'session_id' => $result->session->sessionId()->value(),
                'expires_at' => $result->session->expiresAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        throw new \InvalidArgumentException('Unsupported authentication result.');
    }
}
