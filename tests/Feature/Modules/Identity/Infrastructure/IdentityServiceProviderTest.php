<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Infrastructure;

use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Tests\TestCase;

final class IdentityServiceProviderTest extends TestCase
{
    public function test_password_hasher_contract_is_bound_to_laravel_implementation(): void
    {
        $hasher = $this->app->make(PasswordHasher::class);

        $this->assertInstanceOf(
            LaravelPasswordHasher::class,
            $hasher,
        );

        $this->assertSame(
            $hasher,
            $this->app->make(PasswordHasher::class),
        );
    }


    public function test_user_repository_contract_is_bound_to_eloquent_implementation(): void
    {
        $repository = $this->app->make(UserRepository::class);

        $this->assertInstanceOf(
            EloquentUserRepository::class,
            $repository,
        );

        $this->assertSame(
            $repository,
            $this->app->make(UserRepository::class),
        );
    }
}
