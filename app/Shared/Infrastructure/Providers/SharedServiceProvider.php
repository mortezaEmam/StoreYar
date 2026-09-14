<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            BusinessContext::class,
            CurrentBusinessContext::class,
        );
    }
}
