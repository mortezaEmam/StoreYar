<?php

declare(strict_types=1);

namespace Tests\Feature\StoreYar;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

/**
 * API without business context / foreign tenant must not leak data.
 * Run: php artisan test --filter=TenantIsolationSmokeTest
 */
final class TenantIsolationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_dashboard_requires_auth(): void
    {
        $this->getJson('/api/reports/dashboard')->assertUnauthorized();
    }

    public function test_export_requires_auth(): void
    {
        $this->getJson('/api/export/manifest')->assertUnauthorized();
    }

    public function test_authenticated_without_business_header_is_rejected_or_empty(): void
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'iso@example.com', name: 'Iso'),
        );
        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'iso@example.com',
            'password' => 'password123',
        ])->assertOk()->json('data.token');

        // Missing X-Business-Id should not succeed as another tenant's data
        $response = $this->withToken($token)->getJson('/api/reports/dashboard');
        $this->assertTrue(
            in_array($response->status(), [400, 403, 422], true),
            'Expected missing business context to be rejected, got ' . $response->status(),
        );
    }
}
