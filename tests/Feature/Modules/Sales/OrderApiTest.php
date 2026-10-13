<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Infrastructure\Persistence\Eloquent\OutboxModel;
use StoreYar\Modules\Sales\Domain\Events\OrderConfirmed;
use Tests\TestCase;

final class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     token: string,
     *     organization_id: string,
     *     product_id: string
     * }
     */
    private function contextWithProductAndStock(
        int $stockQty = 10,
    ): array {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'sales@example.com',
                name: 'Sales Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'sales@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', [
                'name' => 'Sales Shop',
            ])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Sample',
                'sku' => 'SKU-001',
                'unit_price_amount' => 5000,
                'currency' => 'IRR',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'test-stock-init-'.uniqid('', true))
            ->postJson('/api/stock', [
                'product_id' => $productId,
                'quantity' => $stockQty,
            ])
            ->assertCreated();

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'product_id' => $productId,
        ];
    }

    /**
     * Confirm an order using a unique idempotency key.
     */
    private function confirmOrder(
        string $token,
        string $organizationId,
        string $orderId,
    ): \Illuminate\Testing\TestResponse {
        return $this->withToken($token)
            ->withHeader('X-Business-Id', $organizationId)
            ->withHeader(
                'Idempotency-Key',
                'test-confirm-' . uniqid('', true),
            )
            ->postJson('/api/orders/' . $orderId . '/confirm');
    }

    public function test_create_order_and_add_line(): void
    {
        $ctx = $this->contextWithProductAndStock();

        $order = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $orderId = $order->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 2,
                'unit_price_amount' => 5000,
            ])
            ->assertOk()
            ->assertJsonPath('data.total_amount', 10000)
            ->assertJsonCount(1, 'data.lines');
    }

    public function test_confirm_decreases_stock(): void
    {
        $ctx = $this->contextWithProductAndStock(10);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-confirm-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 3,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->confirmOrder(
            $ctx['token'],
            $ctx['organization_id'],
            $orderId,
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->getJson('/api/stock/' . $ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);
    }

    public function test_confirm_with_insufficient_stock_keeps_draft(): void
    {
        $ctx = $this->contextWithProductAndStock(2);

        // تضمین: موجودی واقعاً 2 است
        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 2);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 5, // بیشتر از 2
            ])
            ->assertOk()
            ->assertJsonPath('data.lines.0.quantity', 5);

        $this->flushHeaders();

        $response = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-confirm-fail-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm');

        if ($response->status() !== 422) {
            dump($response->status(), $response->json());
        }

        $response->assertUnprocessable();
    }


    public function test_duplicate_line_rejected(): void
    {
        $ctx = $this->contextWithProductAndStock();

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $payload = [
            'product_id' => $ctx['product_id'],
            'quantity' => 1,
            'unit_price_amount' => 1000,
        ];

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', $payload)
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', $payload)
            ->assertUnprocessable();
    }

    public function test_cancel_confirmed_restores_stock(): void
    {
        $ctx = $this->contextWithProductAndStock(10);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 4,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->confirmOrder(
            $ctx['token'],
            $ctx['organization_id'],
            $orderId,
        )->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-cancel-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/' . $ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 10);
    }

    public function test_confirm_writes_outbox_events(): void
    {
        $ctx = $this->contextWithProductAndStock(5);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/' . $orderId . '/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 1,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->confirmOrder(
            $ctx['token'],
            $ctx['organization_id'],
            $orderId,
        )->assertOk();

        $this->assertDatabaseHas('shared_outbox', [
            'aggregate_type' => 'sales.order',
            'aggregate_id' => $orderId,
        ]);

        $this->assertTrue(
            OutboxModel::query()
                ->where('aggregate_id', $orderId)
                ->where('event_type', OrderConfirmed::class)
                ->exists(),
        );
    }
}
