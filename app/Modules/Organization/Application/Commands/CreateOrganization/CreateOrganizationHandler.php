<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\CreateOrganization;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Exceptions\OrganizationAlreadyExists;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateOrganizationHandler implements CommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Organization
    {
        if (! $command instanceof CreateOrganizationCommand) {
            throw new \InvalidArgumentException(
                'CreateOrganizationHandler received an invalid command.',
            );
        }

        if ($this->organizations->findByName($command->name) !== null) {
            throw new OrganizationAlreadyExists($command->name);
        }

        $organization = Organization::create(
            id: OrganizationId::generate(),
            name: $command->name,
            ownerUserId: $command->ownerUserId,
            now: $this->clock->now(),
        );

        $this->organizations->save($organization);

        return $organization;
    }
}
