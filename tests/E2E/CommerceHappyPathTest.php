<?php

declare(strict_types=1);

namespace Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class CommerceHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
        ]);
    }

    public function test_full_commerce_path(): void
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'e2e-owner@example.com',
                name: 'E2E Owner',
            ),
        );
        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'e2e-owner@example.com',
            'password' => 'password123',
        ])->assertOk()->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'E2E Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'E2E Product',
                'sku' => 'E2E-SKU-1',
                'unit_price_amount' => 2500,
                'currency' => 'IRR',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-stock-'.uniqid('', true))
            ->postJson('/api/stock', [
                'product_id' => $productId,
                'quantity' => 20,
            ])
            ->assertCreated();

        // Create empty draft order, then add line (real API contract)
        $orderId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-order-'.uniqid('', true))
            ->postJson('/api/orders')
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-line-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $productId,
                'quantity' => 2,
                'unit_price_amount' => 2500,
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-confirm-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $paymentId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-pay-'.uniqid('', true))
            ->postJson('/api/payments', [
                'order_id' => $orderId,
                'amount' => 5000,
                'currency' => 'IRR',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-pay-ok-'.uniqid('', true))
            ->postJson('/api/payments/'.$paymentId.'/succeed')
            ->assertOk();

        $shipmentId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-ship-'.uniqid('', true))
            ->postJson('/api/shipments', [
                'order_id' => $orderId,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'e2e-ship-go-'.uniqid('', true))
            ->postJson('/api/shipments/'.$shipmentId.'/ship', [
                'tracking_number' => 'E2E-TRACK-1',
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->getJson('/api/audit-log')
            ->assertOk();
    }
}
