<?php

// CreateBranchHandler.php
namespace StoreYar\Modules\Organization\Application\Commands\CreateBranch;

use StoreYar\Modules\Organization\Domain\Contracts\BranchRepository;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Entities\Branch;
use StoreYar\Modules\Organization\Domain\ValueObjects\BranchId;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateBranchHandler implements CommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private BranchRepository $branches,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Branch
    {
        if (! $command instanceof CreateBranchCommand) {
            throw new \InvalidArgumentException(
                'CreateBranchHandler received an invalid command.',
            );
        }

        $organization = $this->organizations->findById(
            OrganizationId::fromString($command->organizationId),
        );

        if ($organization === null) {
            throw new \InvalidArgumentException('Organization not found.');
        }

        if ($organization->ownerUserId() !== $command->actorUserId) {
            throw new \InvalidArgumentException('Not allowed.');
        }

        if (! $organization->status()->isActive()) {
            throw new \InvalidArgumentException(
                'Cannot create branch for a non-active organization.',
            );
        }

        $branch = Branch::create(
            id: BranchId::generate(),
            organizationId: $organization->organizationId(),
            name: $command->name,
            now: $this->clock->now(),
        );

        $this->branches->save($branch);

        return $branch;
    }
}
