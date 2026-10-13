<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class StockInitializeIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'init-idem@example.com', name: 'Init Idem'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'init-idem@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Init Shop'])
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'P',
                'sku' => 'SKU-INIT-IDEM',
                'unit_price_amount' => 1000,
            ])
            ->json('data.id');

        return compact('token', 'orgId', 'productId');
    }

    public function test_initialize_requires_idempotency_key(): void
    {
        $ctx = $this->context();

        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['productId'],
                'quantity' => 5,
            ])
            ->assertStatus(400);
    }

    public function test_initialize_replay_returns_same_stock(): void
    {
        $ctx = $this->context();
        $key = 'init-'.uniqid('', true);

        $first = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/stock', [
                'product_id' => $ctx['productId'],
                'quantity' => 5,
            ])
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'false');

        $second = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/stock', [
                'product_id' => $ctx['productId'],
                'quantity' => 5,
            ])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );
        $this->assertSame(5, $second->json('data.quantity'));
    }
}
