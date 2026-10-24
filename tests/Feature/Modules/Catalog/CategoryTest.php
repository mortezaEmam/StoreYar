<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class CategoryTest extends TestCase
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

    public function test_create_and_list_categories(): void
    {
        [$token, $orgId] = $this->authWithOrg();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->postJson('/api/categories', ['name' => 'Electronics'])
            ->assertCreated();

        $this->withToken($token)
            ->withHeader('X-Business-Id', $orgId)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Electronics']);
    }

    /** @return array{0: string, 1: string} */
    private function authWithOrg(): array
    {
        $commands = $this->app->make(CommandBus::class);
        /** @var User $user */
        $user = $commands->dispatch(new CreateUserCommand(email: 'cat@example.com', name: 'Cat'));
        $commands->dispatch(new SetPasswordCommand(userId: $user->userId()->value(), password: 'password12'));

        $login = $this->postJson('/api/auth/login', [
            'email' => 'cat@example.com',
            'password' => 'password12',
        ])->assertOk();

        $token = $login->json('data.token') ?? $login->json('token');

        // Create org via API
        $org = $this->withToken($token)
            ->postJson('/api/organizations', ['name' => 'Cat Org'])
            ->assertSuccessful();

        $orgId = $org->json('data.id') ?? $org->json('id');

        return [(string) $token, (string) $orgId];
    }
}
