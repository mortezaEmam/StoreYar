<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions;

use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class RevokeAllUserSessionsHandler implements CommandHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof RevokeAllUserSessionsCommand) {
            throw new \InvalidArgumentException(
                'RevokeAllUserSessionsHandler received an invalid command.',
            );
        }

        $this->sessions->revokeAllForUser($command->userId);

        return null;
    }
}
