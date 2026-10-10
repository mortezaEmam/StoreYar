<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string, product_id: string}
     */
    private function contextWithProductAndStock(int $stockQty = 10): array
    {
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
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Sales Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Sellable',
                'sku' => 'SKU-SALE-1',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
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

    public function test_create_order_and_add_line(): void
    {
        $ctx = $this->contextWithProductAndStock();

        $order = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $orderId = $order->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
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
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 3,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);
    }

    public function test_confirm_with_insufficient_stock_keeps_draft(): void
    {
        $ctx = $this->contextWithProductAndStock(2);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 5,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertUnprocessable();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 2);
    }

    public function test_duplicate_line_rejected(): void
    {
        $ctx = $this->contextWithProductAndStock();

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $payload = [
            'product_id' => $ctx['product_id'],
            'quantity' => 1,
            'unit_price_amount' => 1000,
        ];

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', $payload)
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', $payload)
            ->assertUnprocessable();
    }

    public function test_cancel_confirmed_restores_stock(): void
    {
        $ctx = $this->contextWithProductAndStock(10);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 4,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 10);
    }


    public function test_confirm_writes_outbox_events(): void
    {
        $ctx = $this->contextWithProductAndStock(5);

        $orderId = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $ctx['product_id'],
                'quantity' => 1,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $this->assertDatabaseHas('shared_outbox', [
            'aggregate_type' => 'sales.order',
            'aggregate_id' => $orderId,
        ]);

        $this->assertTrue(
            \StoreYar\Shared\Infrastructure\Persistence\Eloquent\OutboxModel::query()
                ->where('aggregate_id', $orderId)
                ->where('event_type', \StoreYar\Modules\Sales\Domain\Events\OrderConfirmed::class)
                ->exists()
        );
    }
}
