<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\RenameOrganization;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RenameOrganizationCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public string $actorUserId,
    ) {}
}
