<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Authorization\Application\Commands\GrantMembership;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand;
use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipAlreadyExists;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class GrantMembershipHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_membership(): void
    {
        $commands = $this->app->make(CommandBus::class);
        $repository = $this->app->make(MembershipRepository::class);

        $membership = $commands->dispatch(
            new GrantMembershipCommand(
                organizationId: '01JORG00000000000000000001',
                userId: '01JOWNERUSER00000000000001',
                role: Role::OWNER,
            ),
        );

        $found = $repository->findByOrganizationAndUser(
            '01JORG00000000000000000001',
            '01JOWNERUSER00000000000001',
        );

        self::assertNotNull($found);
        self::assertTrue($found->role()->isOwner());
        self::assertSame($membership->membershipId()->value(), $found->membershipId()->value());
    }

    public function test_it_rejects_duplicate(): void
    {
        $commands = $this->app->make(CommandBus::class);

        $commands->dispatch(
            new GrantMembershipCommand(
                organizationId: '01JORG00000000000000000001',
                userId: '01JOWNERUSER00000000000001',
                role: Role::OWNER,
            ),
        );

        $this->expectException(MembershipAlreadyExists::class);

        $commands->dispatch(
            new GrantMembershipCommand(
                organizationId: '01JORG00000000000000000001',
                userId: '01JOWNERUSER00000000000001',
                role: Role::ADMIN,
            ),
        );
    }
}
