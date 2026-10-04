<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\SuspendOrganization;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class SuspendOrganizationHandler implements CommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Organization
    {
        if (! $command instanceof SuspendOrganizationCommand) {
            throw new \InvalidArgumentException(
                'SuspendOrganizationHandler received an invalid command.',
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

        $organization->suspend($this->clock->now());
        $this->organizations->save($organization);

        return $organization;
    }
}
