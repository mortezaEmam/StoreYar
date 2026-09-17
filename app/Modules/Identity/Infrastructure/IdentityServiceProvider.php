<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );
    }
}
