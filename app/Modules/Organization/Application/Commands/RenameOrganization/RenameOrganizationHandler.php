<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\RenameOrganization;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class RenameOrganizationHandler implements CommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Organization
    {
        if (! $command instanceof RenameOrganizationCommand) {
            throw new \InvalidArgumentException(
                'RenameOrganizationHandler received an invalid command.',
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

        $existing = $this->organizations->findByName($command->name);

        if (
            $existing !== null
            && ! $existing->organizationId()->equals($organization->organizationId())
        ) {
            throw new OrganizationAlreadyExists($command->name);
        }

        $organization->rename($command->name, $this->clock->now());
        $this->organizations->save($organization);

        return $organization;
    }
}
