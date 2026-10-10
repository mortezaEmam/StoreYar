<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class StockApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string, product_id: string}
     */
    private function contextWithProduct(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'inventory@example.com',
                name: 'Inventory Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'inventory@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Inventory Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $product = $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/products', [
                'name' => 'Stocked Product',
                'sku' => 'SKU-STOCK-1',
            ])
            ->assertCreated();

        return [
            'token' => $token,
            'organization_id' => $orgId,
            'product_id' => $product->json('data.id'),
        ];
    }

    public function test_initialize_stock_requires_auth(): void
    {
        $this->postJson('/api/stock', [
            'product_id' => '01JPROD0000000000000000001',
            'quantity' => 5,
        ])->assertUnauthorized();
    }

    public function test_initialize_stock_requires_business_header(): void
    {
        $ctx = $this->contextWithProduct();

        // پاک کردن headerهای باقی‌مانده از setup
        $this->flushHeaders();

        $this->withToken($ctx['token'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 5,
            ])
            ->assertStatus(400);
    }


    public function test_initialize_stock_success(): void
    {
        $ctx = $this->contextWithProduct();

        $response = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 15,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.product_id', $ctx['product_id'])
            ->assertJsonPath('data.organization_id', $ctx['organization_id'])
            ->assertJsonPath('data.quantity', 15);
    }

    public function test_initialize_stock_duplicate(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 5,
            ])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 10,
            ])
            ->assertUnprocessable();
    }

    public function test_initialize_stock_validation(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => '',
                'quantity' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }


    public function test_adjust_stock_increase_and_decrease(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 10,
            ])
            ->assertCreated();

        $increased = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock/'.$ctx['product_id'].'/adjust', [
                'amount' => 5,
                'direction' => 'increase',
            ]);

        $increased->assertOk()
            ->assertJsonPath('data.quantity', 15);

        $decreased = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock/'.$ctx['product_id'].'/adjust', [
                'amount' => 3,
                'direction' => 'decrease',
            ]);

        $decreased->assertOk()
            ->assertJsonPath('data.quantity', 12);
    }

    public function test_adjust_stock_insufficient(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 2,
            ])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock/'.$ctx['product_id'].'/adjust', [
                'amount' => 10,
                'direction' => 'decrease',
            ])
            ->assertUnprocessable();
    }


    public function test_initialize_stock_for_unknown_product_returns_404(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => (string) \Illuminate\Support\Str::ulid(),
                'quantity' => 1,
            ])
            ->assertNotFound();
    }

    public function test_list_and_show_stock(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/stock', [
                'product_id' => $ctx['product_id'],
                'quantity' => 7,
            ])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quantity', 7)
            ->assertJsonPath('data.0.product_id', $ctx['product_id']);

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.$ctx['product_id'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);
    }

    public function test_show_stock_not_found(): void
    {
        $ctx = $this->contextWithProduct();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/stock/'.(string) \Illuminate\Support\Str::ulid())
            ->assertNotFound();
    }
}
