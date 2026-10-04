<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\ActivateOrganization;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ActivateOrganizationCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $actorUserId,
    ) {}
}
