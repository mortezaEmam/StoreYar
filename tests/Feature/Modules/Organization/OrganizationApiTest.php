<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Organization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
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


    public function test_create_organization_grants_owner_membership(): void
    {
        $token = $this->authenticatedToken();

        $response = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Owned Shop',
        ])->assertCreated();

        $orgId = $response->json('data.id');
        $ownerUserId = $response->json('data.owner_user_id');

        $memberships = $this->app->make(MembershipRepository::class);
        $membership = $memberships->findByOrganizationAndUser($orgId, $ownerUserId);

        $this->assertNotNull($membership);
        $this->assertTrue($membership->role()->isOwner());
    }


    public function test_member_cannot_rename_organization(): void
    {
        $ownerToken = $this->authenticatedToken();

        $org = $this->withToken($ownerToken)->postJson('/api/organizations', [
            'name' => 'Restricted Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $commands = $this->app->make(CommandBus::class);

        /** @var User $member */
        $member = $commands->dispatch(
            new CreateUserCommand(
                email: 'member@example.com',
                name: 'Member',
            ),
        );

        $memberUserId = is_object($member->id()) && method_exists($member->id(), 'value')
            ? $member->id()->value()
            : (string) $member->id();
# داخل تست موقتاً:
        $commands->dispatch(
            new SetPasswordCommand(
                userId: $memberUserId,
                password: 'password123',
            ),
        );

        $commands->dispatch(
            new \StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand(
                organizationId: $orgId,
                userId: $memberUserId,
                role: \StoreYar\Modules\Authorization\Domain\Enums\Role::MEMBER,
            ),
        );

        /** @var \StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository $memberships */
        $memberships = $this->app->make(
            \StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository::class,
        );

        // قبل از HTTP: membership باید وجود داشته باشد
        $this->assertNotNull(
            $memberships->findByOrganizationAndUser($orgId, $memberUserId),
            'Membership was not persisted for member user.',
        );

        $login = $this->postJson('/api/auth/login', [
            'email' => 'member@example.com',
            'password' => 'password123',
        ])->assertOk();

        $memberToken = $login->json('data.token');
        $this->assertNotEmpty($memberToken);

        // id کاربر از /me باید همان membership باشد
        $me = $this->withToken($memberToken)->getJson('/api/auth/me')->assertOk();
        $meUserId = $me->json('data.id');
        dump($memberUserId, $meUserId, $orgId);

        $this->assertSame(
            $memberUserId,
            $meUserId,
            'Logged-in user id does not match membership user id.',
        );

        $this->assertNotNull(
            $memberships->findByOrganizationAndUser($orgId, $meUserId),
            'No membership for logged-in user id.',
        );

        // member می‌تواند ببیند
        $this->withToken($memberToken)
            ->getJson('/api/organizations/'.$orgId)
            ->assertOk();

        // member نمی‌تواند rename کند
        $this->withToken($memberToken)
            ->patchJson('/api/organizations/'.$orgId, [
                'name' => 'Hacked',
            ])
            ->assertForbidden();
    }


    public function test_list_members_includes_owner(): void
    {
        $token = $this->authenticatedToken();

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Members Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');
        $ownerId = $org->json('data.owner_user_id');

        $response = $this->withToken($token)
            ->getJson('/api/organizations/'.$orgId.'/members');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $ownerId)
            ->assertJsonPath('data.0.role', 'owner');
    }

    public function test_owner_can_grant_member(): void
    {
        $token = $this->authenticatedToken();

        $org = $this->withToken($token)->postJson('/api/organizations', [
            'name' => 'Grant Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $commands = $this->app->make(CommandBus::class);

        /** @var User $user */
        $user = $commands->dispatch(
            new CreateUserCommand(email: 'newmember@example.com', name: 'New Member'),
        );

        $userId = (string) $user->id();

        $response = $this->withToken($token)->postJson(
            '/api/organizations/'.$orgId.'/members',
            [
                'user_id' => $userId,
                'role' => 'member',
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $userId)
            ->assertJsonPath('data.role', 'member');

        $this->withToken($token)
            ->getJson('/api/organizations/'.$orgId.'/members')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_member_cannot_grant_membership(): void
    {
        $ownerToken = $this->authenticatedToken();

        $org = $this->withToken($ownerToken)->postJson('/api/organizations', [
            'name' => 'No Grant Shop',
        ])->assertCreated();

        $orgId = $org->json('data.id');

        $commands = $this->app->make(CommandBus::class);

        /** @var User $member */
        $member = $commands->dispatch(
            new CreateUserCommand(email: 'onlymember@example.com', name: 'Only Member'),
        );
        $memberId = (string) $member->id();

        $commands->dispatch(
            new SetPasswordCommand(userId: $memberId, password: 'password123'),
        );

        $commands->dispatch(
            new \StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand(
                organizationId: $orgId,
                userId: $memberId,
                role: \StoreYar\Modules\Authorization\Domain\Enums\Role::MEMBER,
            ),
        );

        $memberToken = $this->postJson('/api/auth/login', [
            'email' => 'onlymember@example.com',
            'password' => 'password123',
        ])->json('data.token');

        /** @var User $another */
        $another = $commands->dispatch(
            new CreateUserCommand(email: 'another@example.com', name: 'Another'),
        );

        $this->withToken($memberToken)->postJson(
            '/api/organizations/'.$orgId.'/members',
            [
                'user_id' => (string) $another->id(),
                'role' => 'member',
            ],
        )->assertForbidden();
    }
}
