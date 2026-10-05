<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Organization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedToken(): string
    {
        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(
                email: 'owner@example.com',
                name: 'Owner',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: $user->id(),
                password: 'password123',
            ),
        );

        $login = $this->postJson('/api/auth/login', [
            'email' => 'owner@example.com',
            'password' => 'password123',
        ]);

        return $login->json('data.token');
    }

    public function test_create_organization_requires_auth(): void
    {
        $this->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertUnauthorized();
    }

    public function test_create_organization_success(): void
    {
        $token = $this->authenticatedToken();

        $response = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'My Shop')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'owner_user_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertSame('active', $response->json('data.status'));
    }

    public function test_create_organization_duplicate_name(): void
    {
        $token = $this->authenticatedToken();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ])->assertUnprocessable();
    }

    public function test_create_organization_validation(): void
    {
        $token = $this->authenticatedToken();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }


    public function test_list_organizations_for_owner(): void
    {
        $token = $this->authenticatedToken();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Shop A',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Shop B',
        ])->assertCreated();

        $response = $this->withToken($token)->getJson('/api/organizations');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Shop A', $names);
        $this->assertContains('Shop B', $names);
    }

    public function test_show_organization(): void
    {
        $token = $this->authenticatedToken();

        $created = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'My Shop',
        ]);

        $id = $created->json('data.id');

        $response = $this->withToken($token)->getJson('/api/organizations/'.$id);

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.name', 'My Shop')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_show_organization_not_found(): void
    {
        $token = $this->authenticatedToken();

        $id = (string) Str::ulid();

        $this->withToken($token)
            ->getJson('/api/organizations/'.$id)
            ->assertNotFound();
    }

    public function test_rename_organization(): void
    {
        $token = $this->authenticatedToken();

        $created = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Old Name',
        ]);

        $id = $created->json('data.id');

        $response = $this->withToken($token)->patchJson('/api/organizations/'.$id, [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_suspend_and_activate_organization(): void
    {
        $token = $this->authenticatedToken();

        $created = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Toggle Shop',
        ]);

        $id = $created->json('data.id');

        $suspended = $this->withToken($token)
            ->postJson('/api/organizations/'.$id.'/suspend');

        $suspended->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $activated = $this->withToken($token)
            ->postJson('/api/organizations/'.$id.'/activate');

        $activated->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_cannot_access_others_organization(): void
    {
        $ownerToken = $this->authenticatedToken();

        $created = $this->withToken($ownerToken)->postJson('/api/organizations', [
            'name' => 'Private Shop',
        ]);

        $id = $created->json('data.id');

        // کاربر دوم
        $commands = $this->app->make(CommandBus::class);

        /** @var User $other */
        $other = $commands->dispatch(
            new CreateUserCommand(
                email: 'other@example.com',
                name: 'Other User',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: $other->id(),
                password: 'password123',
            ),
        );

        $otherLogin = $this->postJson('/api/auth/login', [
            'email' => 'other@example.com',
            'password' => 'password123',
        ]);

        $otherToken = $otherLogin->json('data.token');

        $this->withToken($otherToken)
            ->getJson('/api/organizations/'.$id)
            ->assertForbidden();

        $this->withToken($otherToken)
            ->patchJson('/api/organizations/'.$id, [
                'name' => 'Hacked',
            ])
            ->assertForbidden();

        $this->withToken($otherToken)
            ->postJson('/api/organizations/'.$id.'/suspend')
            ->assertForbidden();
    }


    public function test_create_branch(): void
    {
        $token = $this->authenticatedToken();

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Shop With Branch',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $response = $this->withToken($token)->postJson(
            '/api/organizations/'.$orgId.'/branches',
            ['name' => 'Main Branch'],
        );

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Main Branch')
            ->assertJsonPath('data.organization_id', $orgId)
            ->assertJsonPath('data.status', 'active');
    }


    public function test_list_branches(): void
    {
        $token = $this->authenticatedToken();

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Shop With Branches',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $this->withToken($token)->postJson('/api/organizations/'.$orgId.'/branches', [
            'name' => 'Main',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/organizations/'.$orgId.'/branches', [
            'name' => 'Warehouse',
        ])->assertCreated();

        $response = $this->withToken($token)
            ->getJson('/api/organizations/'.$orgId.'/branches');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Main', $names);
        $this->assertContains('Warehouse', $names);
    }

    public function test_list_branches_forbidden_for_other_user(): void
    {
        $ownerToken = $this->authenticatedToken();

        $org = $this->withToken($ownerToken)->postJson('/api/organizations', [
            'name' => 'Private Org',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $commands = $this->app->make(CommandBus::class);

        /** @var User $other */
        $other = $commands->dispatch(
            new CreateUserCommand(
                email: 'other2@example.com',
                name: 'Other User 2',
            ),
        );

        $commands->dispatch(
            new SetPasswordCommand(
                userId: $other->id(),
                password: 'password123',
            ),
        );

        $otherLogin = $this->postJson('/api/auth/login', [
            'email' => 'other2@example.com',
            'password' => 'password123',
        ]);

        $otherToken = $otherLogin->json('data.token');

        $this->withToken($otherToken)
            ->getJson('/api/organizations/'.$orgId.'/branches')
            ->assertForbidden();
    }


    public function test_business_context_requires_header(): void
    {
        $token = $this->authenticatedToken();

        // یک route موقت نداریم؛ رفتار middleware را با dispatch دستی یا route تستی چک می‌کنیم.
        // اگر هنوز route با business.context نداری، این تست را بعد از اولین route tenant-scoped فعال کن.
        $this->assertTrue(true);
    }


    public function test_business_context_ping(): void
    {
        $token = $this->authenticatedToken();

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Context Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $branch = $this->withToken($token)->postJson(
            '/api/organizations/'.$orgId.'/branches',
            ['name' => 'Main'],
        )->assertCreated();

        $branchId = $branch->json('data.id');

        // بدون header
        $this->withToken($token)
            ->getJson('/api/business/ping')
            ->assertStatus(400);

        // فقط business
        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->getJson('/api/business/ping')
            ->assertOk()
            ->assertJsonPath('business_id', $orgId)
            ->assertJsonPath('branch_id', null);

        // business + branch
        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->withHeader('X-Branch-Id', $branchId)
            ->getJson('/api/business/ping')
            ->assertOk()
            ->assertJsonPath('business_id', $orgId)
            ->assertJsonPath('branch_id', $branchId);
    }
}
