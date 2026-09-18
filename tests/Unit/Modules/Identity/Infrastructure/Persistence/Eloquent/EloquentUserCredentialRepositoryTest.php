<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserCredentialRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\IdentityUserCredentialModel;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\IdentityUserModel;
use Tests\TestCase;

final class EloquentUserCredentialRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_when_password_credential_does_not_exist(): void
    {
        $repository = new EloquentUserCredentialRepository();

        self::assertNull(
            $repository->findPasswordHash(
                UserId::generate(),
            ),
        );
    }

    public function test_it_saves_and_reads_password_hash(): void
    {
        $userId = UserId::generate();

        IdentityUserModel::query()->create([
            'id' => $userId->value(),
            'email' => 'credential@example.com',
            'name' => 'Credential User',
            'status' => 'active',
            'version' => 0,
        ]);

        $repository = new EloquentUserCredentialRepository();

        $repository->savePasswordHash(
            userId: $userId,
            passwordHash: 'hashed-password-value',
        );

        self::assertSame(
            'hashed-password-value',
            $repository->findPasswordHash($userId),
        );

        self::assertDatabaseHas('identity_user_credentials', [
            'user_id' => $userId->value(),
            'password_hash' => 'hashed-password-value',
        ]);
    }

    public function test_it_updates_existing_password_hash(): void
    {
        $userId = UserId::generate();

        IdentityUserModel::query()->create([
            'id' => $userId->value(),
            'email' => 'update-credential@example.com',
            'name' => 'Credential User',
            'status' => 'active',
            'version' => 0,
        ]);

        IdentityUserCredentialModel::query()->create([
            'user_id' => $userId->value(),
            'password_hash' => 'old-hash',
        ]);

        $repository = new EloquentUserCredentialRepository();

        $repository->savePasswordHash(
            userId: $userId,
            passwordHash: 'new-hash',
        );

        self::assertSame(
            'new-hash',
            $repository->findPasswordHash($userId),
        );

        self::assertDatabaseCount('identity_user_credentials', 1);
    }
}
