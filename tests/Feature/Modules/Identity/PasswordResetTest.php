<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Infrastructure\Security\ArrayPasswordResetNotifier;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
        ]);
        $this->app->instance(PasswordResetNotifier::class, new ArrayPasswordResetNotifier());
    }

    public function test_forgot_password_always_returns_202(): void
    {
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.com',
        ])->assertStatus(202);
    }

    public function test_reset_password_with_valid_token(): void
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'reset@example.com', name: 'Reset User'),
        );
        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'oldpassword1'),
        );

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertStatus(202);

        /** @var ArrayPasswordResetNotifier $notifier */
        $notifier = $this->app->make(PasswordResetNotifier::class);
        $token = $notifier->lastTokenFor('reset@example.com');
        self::assertNotNull($token);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'email' => 'reset@example.com',
            'password' => 'newpassword1',
        ])->assertOk();
    }

    public function test_reset_with_bad_token_fails(): void
    {
        $commands = $this->app->make(CommandBus::class);
        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'bad@example.com', name: 'Bad'),
        );
        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'oldpassword1'),
        );

        $this->postJson('/api/auth/forgot-password', ['email' => 'bad@example.com'])
            ->assertStatus(202);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'bad@example.com',
            'token' => str_repeat('a', 64),
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(422);
    }
}
