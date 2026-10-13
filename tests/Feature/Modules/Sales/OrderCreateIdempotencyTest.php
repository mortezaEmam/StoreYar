<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrderCreateIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAndOrg(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'create-idem@example.com', name: 'Create Idem'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'create-idem@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Create Idem Shop'])
            ->json('data.id');

        return compact('token', 'orgId');
    }

    public function test_create_order_requires_idempotency_key(): void
    {
        ['token' => $token, 'orgId' => $orgId] = $this->tokenAndOrg();

        // بدون Idempotency-Key
        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/orders')
            ->assertStatus(400);
    }

    public function test_create_order_replay_returns_same_order(): void
    {
        ['token' => $token, 'orgId' => $orgId] = $this->tokenAndOrg();
        $key = 'create-order-'.uniqid();

        $first = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders')
            ->assertCreated();

        $second = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders')
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );
    }
}
