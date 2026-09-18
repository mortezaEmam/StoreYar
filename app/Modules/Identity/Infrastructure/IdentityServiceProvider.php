<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );


        $this->app->singleton(
            UserRepository::class,
            EloquentUserRepository::class,
        );
    }
}
