<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RevokeSession;

use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class RevokeSessionHandler implements CommandHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof RevokeSessionCommand) {
            throw new \InvalidArgumentException(
                'RevokeSessionHandler received an invalid command.',
            );
        }

        $this->sessions->revoke($command->sessionId);

        return null;
    }
}
