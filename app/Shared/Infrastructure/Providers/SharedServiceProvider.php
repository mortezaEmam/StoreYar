<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use StoreYar\Shared\Application\Contracts\Audit\AuditRecorder;
use StoreYar\Shared\Application\Audit\DefaultAuditRecorder;
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

        $this->app->scoped(
            AuditRecorder::class,
            DefaultAuditRecorder::class,
        );
    }
}
