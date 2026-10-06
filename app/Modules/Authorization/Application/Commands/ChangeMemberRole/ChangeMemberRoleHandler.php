<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\ChangeMemberRole;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\Exceptions\CannotRemoveLastOwner;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipNotFound;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ChangeMemberRoleHandler implements CommandHandler
{
    public function __construct(
        private MembershipRepository $memberships,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Membership
    {
        if (! $command instanceof ChangeMemberRoleCommand) {
            throw new \InvalidArgumentException(
                'ChangeMemberRoleHandler received an invalid command.',
            );
        }

        $membership = $this->memberships->findByOrganizationAndUser(
            $command->organizationId,
            $command->targetUserId,
        );

        if ($membership === null) {
            throw new MembershipNotFound();
        }

        if (
            $membership->role()->isOwner()
            && $command->newRole !== Role::OWNER
        ) {
            $this->assertNotLastOwner($command->organizationId);
        }

        $membership->changeRole($command->newRole, $this->clock->now());
        $this->memberships->save($membership);

        return $membership;
    }

    private function assertNotLastOwner(string $organizationId): void
    {
        $owners = array_filter(
            $this->memberships->findByOrganizationId($organizationId),
            static fn (Membership $m) => $m->role()->isOwner(),
        );

        if (count($owners) <= 1) {
            throw new CannotRemoveLastOwner();
        }
    }
}
