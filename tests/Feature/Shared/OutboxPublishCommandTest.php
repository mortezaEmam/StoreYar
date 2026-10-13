<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Infrastructure\Persistence\Eloquent\OutboxModel;
use Tests\TestCase;

final class OutboxPublishCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_pending_outbox_rows(): void
    {
        Log::spy();

        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'outbox@example.com', name: 'Outbox User'),
        );

        $commands->dispatch(
            new SetPasswordCommand(userId: (string) $user->id(), password: 'password123'),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'outbox@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $orgId = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Outbox Shop'])
            ->json('data.id');

        $productId = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Sample',
                'sku' => 'SKU-001',
                'unit_price_amount' => 5000,
                'currency' => 'IRR',
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
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders')
            ->json('data.id');

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'test-create-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/lines', [
                'product_id' => $productId,
                'quantity' => 1,
                'unit_price_amount' => 1000,
            ])
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('Idempotency-Key', 'test-confirm-'.uniqid('', true))
            ->postJson('/api/orders/'.$orderId.'/confirm')
            ->assertOk();

        $this->assertTrue(
            OutboxModel::query()->whereNull('processed_at')->exists(),
        );

        $exitCode = Artisan::call('outbox:publish', ['--limit' => 50]);

        self::assertSame(0, $exitCode);

        $this->assertFalse(
            OutboxModel::query()
                ->where('aggregate_id', $orderId)
                ->whereNull('processed_at')
                ->exists(),
        );

        $this->assertTrue(
            OutboxModel::query()
                ->where('aggregate_id', $orderId)
                ->whereNotNull('processed_at')
                ->exists(),
        );
    }
}
