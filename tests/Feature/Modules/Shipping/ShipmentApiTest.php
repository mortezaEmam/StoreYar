<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string, order_id: string}
     */
    private function contextWithPaidOrder(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'ship@example.com', name: 'Ship User'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'ship@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Ship Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'P',
                'sku' => 'SKU-SHIP-1',
                'unit_price_amount' => 1000,
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
                'quantity' => 1,
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'confirm-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $paymentId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'pay-'.uniqid('', true))
            ->postJson('/api/payments', ['order_id' => $orderId])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/payments/'.$paymentId.'/succeed')
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->getJson('/api/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'order_id' => $orderId,
        ];
    }

    public function test_create_ship_and_deliver(): void
    {
        $ctx = $this->contextWithPaidOrder();

        $id = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'ship-create-'.uniqid('', true))
            ->postJson('/api/shipments', ['order_id' => $ctx['order_id']])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order_id', $ctx['order_id'])
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/shipments/'.$id.'/ship', [
                'tracking_number' => 'TRK-123',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.tracking_number', 'TRK-123');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/shipments/'.$id.'/deliver')
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_create_on_unpaid_order_rejected(): void
    {
        $ctx = $this->contextWithPaidOrder();

        $draftId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'create-draft-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/shipments', [
                'order_id' => $draftId,
            ])
            ->assertUnprocessable();
    }

    public function test_duplicate_shipment_rejected(): void
    {
        $ctx = $this->contextWithPaidOrder();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'ship-create-'.uniqid('', true))
            ->postJson('/api/shipments', ['order_id' => $ctx['order_id']])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'ship-create-'.uniqid('', true))
            ->postJson('/api/shipments', ['order_id' => $ctx['order_id']])
            ->assertUnprocessable();
    }


    public function test_create_requires_idempotency_key(): void
    {
        $ctx = $this->contextWithPaidOrder();
        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/shipments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertStatus(400);
    }

    public function test_create_shipment_is_idempotent(): void
    {
        $ctx = $this->contextWithPaidOrder();
        $key = 'ship-idem-'.uniqid('', true);

        $first = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/shipments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertCreated();

        $second = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/shipments', [
                'order_id' => $ctx['order_id'],
            ])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );
    }
}
