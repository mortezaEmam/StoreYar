<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrderConfirmIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string, product_id: string}
     */
    private function contextWithStock(int $qty = 10): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'idempotency@example.com',
                name: 'Idempotency User',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'idempotency@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Idem Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Item',
                'sku' => 'SKU-IDEM-1',
                'unit_price_amount' => 1000,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/stock', [
                'product_id' => $productId,
                'quantity' => $qty,
            ])
            ->assertCreated();

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'product_id' => $productId,
        ];
    }

    public function test_confirm_requires_idempotency_key(): void
    {
        $ctx = $this->contextWithStock();

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 1,
            ])
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertStatus(400);
    }

    public function test_confirm_is_idempotent_and_does_not_double_consume_stock(): void
    {
        $ctx = $this->contextWithStock(10);
        $key = 'confirm-key-'.uniqid('', true);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 3,
            ])
            ->assertOk();

        $first = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders/'.$orderId.'/confirm');

        $first->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertHeader('Idempotency-Replayed', 'false');

        $second = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders/'.$orderId.'/confirm');

        $second->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertHeader('Idempotency-Replayed', 'true');

        // Stock decreased only once: 10 - 3 = 7
        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);
    }
}
