<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Organization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedToken(): string
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'owner@example.com',
                name: 'Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: $user->id(),
                password: 'password123',
            ),
        );

        $login = $this->postJson('/api/auth/login', [
            'email' => 'owner@example.com',
            'password' => 'password123',
        ]);

        return $login->json('data.token');
    }

    public function test_create_organization_requires_auth(): void
    {
        $this->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertUnauthorized();
    }

    public function test_create_organization_success(): void
    {
        $token = $this->authenticatedToken();

        $response = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'My Shop')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'owner_user_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertSame('active', $response->json('data.status'));
    }

    public function test_create_organization_duplicate_name(): void
    {
        $token = $this->authenticatedToken();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertUnprocessable();
    }

    public function test_create_organization_validation(): void
    {
        $token = $this->authenticatedToken();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
