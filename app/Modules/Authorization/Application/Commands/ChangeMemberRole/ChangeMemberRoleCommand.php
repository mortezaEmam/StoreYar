<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\ChangeMemberRole;

use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ChangeMemberRoleCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $targetUserId,
        public Role $newRole,
        public string $actorUserId,
    ) {}
}
