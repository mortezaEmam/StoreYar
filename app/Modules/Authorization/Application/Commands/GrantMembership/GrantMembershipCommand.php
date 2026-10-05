<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\GrantMembership;

use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class GrantMembershipCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public Role $role,
    ) {}
}
