<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Contracts;

use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use Tests\TestCase;

final class UserCredentialRepositoryTest extends TestCase
{
    public function test_contract_can_be_implemented(): void
    {
        $repository = new class implements UserCredentialRepository {
            public function findPasswordHash(UserId $userId): ?string
            {
                return null;
            }

            public function savePasswordHash(
                UserId $userId,
                string $passwordHash,
            ): void {
            }
        };

        $userId = UserId::generate();

        self::assertNull(
            $repository->findPasswordHash($userId),
        );

        $repository->savePasswordHash(
            $userId,
            'hashed-password',
        );

        self::assertTrue(
            $repository instanceof UserCredentialRepository,
        );
    }
}
