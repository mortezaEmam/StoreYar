<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Commands\RevokeMembership;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RevokeMembershipCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $targetUserId,
        public string $actorUserId,
    ) {}
}
