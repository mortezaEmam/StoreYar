<?php

declare(strict_types=1);

namespace StoreYar\Modules\Reporting\Infrastructure;

use Illuminate\Support\ServiceProvider;

/**
 * Reporting is query-only for Phase 10; no command bus registrations required.
 */
final class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // reserved for future report caches / query handlers
    }
}
