<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Payments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string, order_id: string, product_id: string}
     */
    private function contextWithConfirmedOrder(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'pay@example.com', name: 'Pay User'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'pay@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Pay Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'P',
                'sku' => 'SKU-PAY-1',
                'unit_price_amount' => 1500,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'init-'.uniqid('', true))
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

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'order_id' => $orderId,
            'product_id' => $productId,
        ];
    }

    public function test_initiate_requires_idempotency_key(): void
    {
        $ctx = $this->contextWithConfirmedOrder();
        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertStatus(400);
    }

    public function test_initiate_payment_on_confirmed_order(): void
    {
        $ctx = $this->contextWithConfirmedOrder();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'pay-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order_id', $ctx['order_id'])
            ->assertJsonPath('data.amount', 3000);
    }

    public function test_initiate_on_draft_order_rejected(): void
    {
        $ctx = $this->contextWithConfirmedOrder();

        $draftId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'create-draft-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'pay-draft-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $draftId,
            ])
            ->assertUnprocessable();
    }

    public function test_initiate_is_idempotent(): void
    {
        $ctx = $this->contextWithConfirmedOrder();
        $key = 'pay-idem-'.uniqid('', true);

        $first = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertCreated();

        $second = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );
    }

    public function test_succeed_and_fail_payment(): void
    {
        $ctx = $this->contextWithConfirmedOrder();

        $id = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'pay-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/payments/'.$id.'/succeed')
            ->assertOk()
            ->assertJsonPath('data.status', 'succeeded');

        $id2 = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'pay2-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/payments/'.$id2.'/fail', [
                'reason' => 'declined',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'failed');
    }
}
