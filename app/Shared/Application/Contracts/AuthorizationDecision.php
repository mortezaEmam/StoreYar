<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts;

final readonly class AuthorizationDecision
{
    private function __construct(
        private bool $allowed,
        private string $code,
        private ?string $reason = null,
    ) {
    }

    public static function allow(
        string $code = 'authorization.allowed',
        ?string $reason = null,
    ): self {
        return new self(
            allowed: true,
            code: $code,
            reason: $reason,
        );
    }

    public static function deny(
        string $code = 'authorization.permission_denied',
        ?string $reason = null,
    ): self {
        return new self(
            allowed: false,
            code: $code,
            reason: $reason,
        );
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function denied(): bool
    {
        return ! $this->allowed;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
