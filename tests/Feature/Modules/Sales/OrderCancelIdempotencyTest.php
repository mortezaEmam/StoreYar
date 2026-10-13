<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrderCancelIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function contextWithConfirmedOrder(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'cancel-idem@example.com', name: 'Cancel Idem'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'cancel-idem@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Cancel Shop'])
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'P',
                'sku' => 'SKU-CANCEL-IDEM',
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

        $orderId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $productId,
                'quantity' => 2,
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'confirm-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        return compact('token', 'orgId', 'productId', 'orderId');
    }

    public function test_cancel_requires_idempotency_key(): void
    {
        $ctx = $this->contextWithConfirmedOrder();

        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->postJson('/api/orders/'.$ctx['orderId'].'/cancel')
            ->assertStatus(400);
    }

    public function test_cancel_replay_does_not_double_restock(): void
    {
        $ctx = $this->contextWithConfirmedOrder();
        $key = 'cancel-'.uniqid('', true);

        $first = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders/'.$ctx['orderId'].'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertHeader('Idempotency-Replayed', 'false');

        $second = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/orders/'.$ctx['orderId'].'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertHeader('Idempotency-Replayed', 'true');

        // confirmed consumed 2; cancel restored once → back to 10
        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['orgId'])
            ->getJson('/api/stock/'.$ctx['productId'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 10);
    }
}
