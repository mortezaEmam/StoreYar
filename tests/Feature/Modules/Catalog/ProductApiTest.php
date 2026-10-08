<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{token: string, organization_id: string}
     */
    private function authenticatedOwnerContext(): array
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'catalog@example.com',
                name: 'Catalog Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $user->id(),
                password: 'password123',
            ),
        );

        $token = $this->postJson('/api/auth/login', [
            'email' => 'catalog@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Catalog Shop',
        ])->assertCreated();

        return [
            'token' => $token,
            'organization_id' => $org->json('data.id'),
        ];
    }

    public function test_create_product_requires_auth(): void
    {
        $this->postJson('/api/products', [
            'name' => 'P1',
            'sku' => 'SKU-1',
        ])->assertUnauthorized();
    }

    public function test_create_product_requires_business_header(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->postJson('/api/products', [
                'name' => 'P1',
                'sku' => 'SKU-1',
            ])
            ->assertStatus(400);
    }

    public function test_create_product_success(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $response = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Sample Product',
                'sku' => 'SKU-001',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Sample Product')
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.organization_id', $ctx['organization_id']);
    }

    public function test_create_product_duplicate_sku(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'P1',
                'sku' => 'SKU-001',
            ])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'P2',
                'sku' => 'SKU-001',
            ])
            ->assertUnprocessable();
    }

    public function test_create_product_validation(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => '',
                'sku' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'sku']);
    }


    public function test_list_products(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Product A',
                'sku' => 'SKU-A',
            ])
            ->assertCreated();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Product B',
                'sku' => 'SKU-B',
            ])
            ->assertCreated();

        $response = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Product A', $names);
        $this->assertContains('Product B', $names);
    }

    public function test_show_product(): void
    {


        $ctx = $this->authenticatedOwnerContext();

        $created = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Show Me',
                'sku' => 'SKU-SHOW',
            ])
            ->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/products/'.$id)
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'Show Me')
            ->assertJsonPath('data.sku', 'SKU-SHOW');
    }

    public function test_show_product_not_found(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/products/'.(string) \Illuminate\Support\Str::ulid())
            ->assertNotFound();
    }

    public function test_show_product_from_other_organization_is_hidden(): void
    {
        $ctxA = $this->authenticatedOwnerContext();

        $product = $this->withToken($ctxA['token'])
            ->withHeader('X-Business-Id', $ctxA['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Private',
                'sku' => 'SKU-PRIV',
            ])
            ->assertCreated();

        $productId = $product->json('data.id');

        // کاربر/سازمان دوم
        $commands = $this->app->make(CommandBus::class);

        /** @var User $other */
        $other = $commands->dispatch(
            new CreateUserCommand(email: 'other-catalog@example.com', name: 'Other'),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: (string) $other->id(),
                password: 'password123',
            ),
        );

        $otherToken = $this->postJson('/api/auth/login', [
            'email' => 'other-catalog@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $otherOrg = $this->withToken($otherToken)->postJson('/api/organizations', [
            'name' => 'Other Shop',
        ])->assertCreated();

        $this->withToken($otherToken)
            ->withHeader('X-Business-Id', $otherOrg->json('data.id'))
            ->getJson('/api/products/'.$productId)
            ->assertNotFound();
    }
}
