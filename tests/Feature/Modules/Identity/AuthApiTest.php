<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPassword(
        string $email = 'user@example.com',
        string $password = 'password123',
        string $name = 'Test User',
    ): User {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: $email,
                name: $name,
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: $user->id(),
                password: $password,
            ),
        );

        return $user;
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->createUserWithPassword();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'session_id',
                    'expires_at',
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertSame('user@example.com', $response->json('data.user.email'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $this->createUserWithPassword();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_me_with_valid_token(): void
    {
        $this->createUserWithPassword();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $login->json('data.token');

        $response = $this->withToken($token)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', 'user@example.com')
            ->assertJsonPath('data.name', 'Test User');
    }

    public function test_logout_revokes_session(): void
    {
        $this->createUserWithPassword();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $login->json('data.token');

        $logout = $this->withToken($token)->postJson('/api/auth/logout');

        $logout->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $me = $this->withToken($token)->getJson('/api/auth/me');

        $me->assertUnauthorized();
    }

    public function test_rotate_issues_new_token_and_invalidates_old(): void
    {
        $this->createUserWithPassword();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $oldToken = $login->json('data.token');

        $rotate = $this->withToken($oldToken)->postJson('/api/auth/rotate');

        $rotate->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'session_id',
                    'expires_at',
                ],
            ]);

        $newToken = $rotate->json('data.token');

        $this->assertNotSame($oldToken, $newToken);

        $this->withToken($oldToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this->withToken($newToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'user@example.com');
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'session_id',
                    'expires_at',
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'status',
                    ],
                ],
            ])
            ->assertJsonPath('data.user.email', 'new@example.com')
            ->assertJsonPath('data.user.name', 'New User');

        $token = $response->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_register_validation_errors(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'bad',
            'name' => '',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'name', 'password']);
    }

    public function test_register_duplicate_email(): void
    {
        $this->createUserWithPassword(email: 'exists@example.com');

        $response = $this->postJson('/api/auth/register', [
            'email' => 'exists@example.com',
            'name' => 'Another User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
    }


    public function test_login_is_rate_limited(): void
    {
        $this->createUserWithPassword();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
