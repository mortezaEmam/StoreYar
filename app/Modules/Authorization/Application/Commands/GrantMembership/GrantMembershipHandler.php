<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\GrantMembership;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Exceptions\MembershipAlreadyExists;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class GrantMembershipHandler implements CommandHandler
{
    public function __construct(
        private MembershipRepository $memberships,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Membership
    {
        if (! $command instanceof GrantMembershipCommand) {
            throw new \InvalidArgumentException(
                'GrantMembershipHandler received an invalid command.',
            );
        }

        $existing = $this->memberships->findByOrganizationAndUser(
            $command->organizationId,
            $command->userId,
        );

        if ($existing !== null) {
            throw new MembershipAlreadyExists(
                $command->organizationId,
                $command->userId,
            );
        }

        $membership = Membership::create(
            id: MembershipId::generate(),
            organizationId: $command->organizationId,
            userId: $command->userId,
            role: $command->role,
            now: $this->clock->now(),
        );

        $this->memberships->save($membership);

        return $membership;
    }
}
