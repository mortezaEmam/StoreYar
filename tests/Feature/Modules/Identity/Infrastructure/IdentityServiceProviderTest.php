<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Infrastructure;

use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
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
}
