<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\RevokeMembership;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Exceptions\CannotRemoveLastOwner;
use StoreYar\Modules\Authorization\Domain\Exceptions\CannotRevokeOwnMembership;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipNotFound;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class RevokeMembershipHandler implements CommandHandler
{
    public function __construct(
        private MembershipRepository $memberships,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof RevokeMembershipCommand) {
            throw new \InvalidArgumentException(
                'RevokeMembershipHandler received an invalid command.',
            );
        }

        if ($command->actorUserId === $command->targetUserId) {
            throw new CannotRevokeOwnMembership();
        }

        $membership = $this->memberships->findByOrganizationAndUser(
            $command->organizationId,
            $command->targetUserId,
        );

        if ($membership === null) {
            throw new MembershipNotFound();
        }

        if ($membership->role()->isOwner()) {
            $owners = array_filter(
                $this->memberships->findByOrganizationId($command->organizationId),
                static fn (Membership $m) => $m->role()->isOwner(),
            );

            if (count($owners) <= 1) {
                throw new CannotRemoveLastOwner();
            }
        }
        $this->memberships->delete($membership);

        return null;
    }
}
