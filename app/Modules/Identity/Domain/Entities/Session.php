<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Entities;

use DateTimeImmutable;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Domain\Entities\Entity;

final class Session extends Entity
{
    private bool $revoked = false;

    private function __construct(
        SessionId $sessionId,
        private readonly string $userId,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
    ) {
        parent::__construct($sessionId->value());
    }

    public static function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ): self {
        self::assertRequired($userId, 'Session user ID');
        self::assertRequired($tokenHash, 'Session token hash');

        return new self(
            sessionId: $sessionId,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
        );
    }

    public static function reconstitute(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        bool $revoked,
    ): self {
        $session = self::create(
            sessionId: $sessionId,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
        );

        $session->revoked = $revoked;

        return $session;
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isActive(DateTimeImmutable $now): bool
    {
        return ! $this->revoked && ! $this->isExpired($now);
    }

    public function sessionId(): SessionId
    {
        return SessionId::fromString($this->id());
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    private static function assertRequired(
        string $value,
        string $field,
    ): void {
        if (trim($value) === '') {
            throw new \InvalidArgumentException(
                $field . ' is required.',
            );
        }
    }
}
