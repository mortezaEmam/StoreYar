<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

interface PasswordResetTokenRepository
{
    /**
     * Store a SHA-256 hash of the plain token for the email (replaces prior token).
     */
    public function store(string $email, string $tokenHash, \DateTimeImmutable $expiresAt): void;

    public function findHashByEmail(string $email): ?string;

    public function deleteByEmail(string $email): void;
}
