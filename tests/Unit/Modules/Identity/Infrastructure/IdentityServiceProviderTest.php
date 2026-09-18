<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Infrastructure;

use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserCredentialRepository;
use Tests\TestCase;

final class IdentityServiceProviderTest extends TestCase
{
    public function test_user_credential_repository_is_bound_to_eloquent_implementation(): void
    {
        self::assertInstanceOf(
            EloquentUserCredentialRepository::class,
            $this->app->make(UserCredentialRepository::class),
        );
    }
}
