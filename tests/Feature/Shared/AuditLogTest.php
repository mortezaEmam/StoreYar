<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_order_writes_audit_and_list_endpoint_works(): void
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'audit@example.com', name: 'Audit User'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'audit@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Audit Shop'])
            ->assertCreated()
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Audit Product',
                'sku' => 'SKU-AUDIT-1',
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

        $this->assertDatabaseHas('shared_audit_log', [
            'action' => 'sales.order.confirmed',
            'resource_type' => 'sales.order',
            'resource_id' => $orderId,
            'organization_id' => $orgId,
        ]);

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->getJson('/api/audit-log')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['page', 'per_page', 'total', 'last_page'],
            ]);
    }
}
