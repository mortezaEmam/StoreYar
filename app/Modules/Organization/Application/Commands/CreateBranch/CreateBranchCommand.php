<?php

// CreateBranchCommand.php
namespace StoreYar\Modules\Organization\Application\Commands\CreateBranch;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateBranchCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public string $actorUserId,
    ) {}
}
