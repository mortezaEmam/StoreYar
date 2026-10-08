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
}
