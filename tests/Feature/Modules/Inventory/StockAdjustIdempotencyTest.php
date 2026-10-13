<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class StockAdjustIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'adj-idem@example.com', name: 'Adj Idem'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'adj-idem@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Adj Shop'])
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'P',
                'sku' => 'SKU-ADJ-IDEM',
                'unit_price_amount' => 1000,
            ])
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'test-stock-init-'.uniqid('', true))
            ->postJson('/api/stock', [
                'product_id' => $productId,
                'quantity' => 10,
            ])
            ->assertCreated();

        return compact('token', 'orgId', 'productId');
    }

    public function test_adjust_requires_idempotency_key(): void
    {
        $ctx = $this->context(); // init stock اینجا key دارد

        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            // بدون Idempotency-Key
            ->postJson('/api/stock/'.$ctx['productId'].'/adjust', [
                'amount' => 1,
                'direction' => 'increase',
            ])
            ->assertStatus(400);
    }

    public function test_adjust_replay_does_not_double_apply(): void
    {
        $ctx = $this->context();
        $key = 'adj-'.uniqid();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/stock/'.$ctx['productId'].'/adjust', [
                'amount' => 2,
                'direction' => 'increase',
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 12)
            ->assertHeader('Idempotency-Replayed', 'false');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/stock/'.$ctx['productId'].'/adjust', [
                'amount' => 2,
                'direction' => 'increase',
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 12)
            ->assertHeader('Idempotency-Replayed', 'true');
    }
}
