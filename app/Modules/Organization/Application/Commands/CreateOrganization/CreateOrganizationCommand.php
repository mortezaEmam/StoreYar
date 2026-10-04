<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Commands\CreateOrganization;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateOrganizationCommand implements Command
{
    public function __construct(
        public string $name,
        public string $ownerUserId,
    ) {}
}
