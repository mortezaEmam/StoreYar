<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        ])->assertOk()->json('data.token');

        $org = $this->withToken($token)
            ->postJson('/api/organizations', [
                'name' => 'Catalog Shop',
            ])
            ->assertCreated();

        return [
            'token' => $token,
            'organization_id' => $org->json('data.id'),
        ];
    }

    /**
     * Create a product using the authenticated organization context.
     */
    private function createProduct(
        array $ctx,
        string $name = 'Sample Product',
        string $sku = 'SKU-001',
        int $amount = 5000,
    ) {
        return $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => $name,
                'sku' => $sku,
                'unit_price_amount' => $amount,
                'currency' => 'IRR',
            ]);
    }

    public function test_create_product_requires_auth(): void
    {
        $this->postJson('/api/products', [
            'name' => 'Sample Product',
            'sku' => 'SKU-001',
            'unit_price_amount' => 5000,
            'currency' => 'IRR',
        ])->assertUnauthorized();
    }

    public function test_create_product_requires_business_header(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->withToken($ctx['token'])
            ->postJson('/api/products', [
                'name' => 'Sample Product',
                'sku' => 'SKU-001',
                'unit_price_amount' => 5000,
                'currency' => 'IRR',
            ])
            ->assertStatus(400);
    }

    public function test_create_product_success(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $response = $this->createProduct(
            $ctx,
            'Sample Product',
            'SKU-001',
        );

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Sample Product')
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath(
                'data.organization_id',
                $ctx['organization_id'],
            );
    }

    public function test_create_product_duplicate_sku(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->createProduct($ctx, 'Sample Product', 'SKU-001')
            ->assertCreated();

        $this->createProduct($ctx, 'Another Product', 'SKU-001')
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
                'unit_price_amount' => 5000,
                'currency' => 'IRR',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'sku']);
    }

    public function test_list_products(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $this->createProduct($ctx, 'Product A', 'SKU-A', 5000)
            ->assertCreated();

        $this->createProduct($ctx, 'Product B', 'SKU-B', 6000)
            ->assertCreated();

        $response = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))
            ->pluck('name')
            ->all();

        $this->assertContains('Product A', $names);
        $this->assertContains('Product B', $names);
    }

    public function test_show_product(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $created = $this->createProduct(
            $ctx,
            'Show Me',
            'SKU-SHOW',
        )->assertCreated();

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
            ->getJson('/api/products/'.(string) Str::ulid())
            ->assertNotFound();
    }

    public function test_show_product_from_other_organization_is_hidden(): void
    {
        $ctxA = $this->authenticatedOwnerContext();

        $product = $this->createProduct(
            $ctxA,
            'Private Product',
            'SKU-PRIVATE',
        )->assertCreated();

        $productId = $product->json('data.id');

        $commands = $this->app->make(CommandBus::class);

        /** @var User $other */
        $other = $commands->dispatch(
            new CreateUserCommand(
                email: 'other-catalog@example.com',
                name: 'Other',
            ),
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
        ])->assertOk()->json('data.token');

        $otherOrg = $this->withToken($otherToken)
            ->postJson('/api/organizations', [
                'name' => 'Other Shop',
            ])
            ->assertCreated();

        $this->withToken($otherToken)
            ->withHeader(
                'X-Business-Id',
                $otherOrg->json('data.id'),
            )
            ->getJson('/api/products/'.$productId)
            ->assertNotFound();
    }

    public function test_activate_and_archive_product(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $created = $this->createProduct(
            $ctx,
            'Lifecycle Product',
            'SKU-LIFECYCLE',
        )->assertCreated();

        $id = $created->json('data.id');

        $this->assertSame('draft', $created->json('data.status'));

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products/'.$id.'/activate')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products/'.$id.'/archive')
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_cannot_activate_archived_product(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $created = $this->createProduct(
            $ctx,
            'Archived Product',
            'SKU-ARCHIVED',
        )->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products/'.$id.'/archive')
            ->assertOk();

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products/'.$id.'/activate')
            ->assertUnprocessable();
    }

    public function test_rename_product(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $created = $this->createProduct(
            $ctx,
            'Original Name',
            'SKU-REN',
        )->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->patchJson('/api/products/'.$id, [
                'name' => 'New Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.sku', 'SKU-REN');
    }

    public function test_change_product_price(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $created = $this->createProduct(
            $ctx,
            'Priced Product',
            'SKU-PRICE',
            1000,
        )->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->patchJson('/api/products/'.$id.'/price', [
                'unit_price_amount' => 2500,
                'currency' => 'IRR',
            ])
            ->assertOk()
            ->assertJsonPath('data.unit_price_amount', 2500);
    }


    public function test_create_product_writes_outbox_event(): void
    {
        $ctx = $this->authenticatedOwnerContext();

        $id = $this->withToken($ctx['token'])
            ->withHeader('X-Business-Id', $ctx['organization_id'])
            ->postJson('/api/products', [
                'name' => 'Outbox Product',
                'sku' => 'SKU-OB-P',
                'unit_price_amount' => 1000,
                'currency' => 'IRR',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('shared_outbox', [
            'aggregate_type' => 'catalog.product',
            'aggregate_id' => $id,
        ]);
    }
}
