<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\SetPassword;

use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final readonly class SetPasswordHandler implements CommandHandler
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private UserCredentialRepository $credentials,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof SetPasswordCommand) {
            throw new \InvalidArgumentException(
                'SetPasswordHandler received an invalid command.',
            );
        }

        $userId = UserId::fromString($command->userId);

        $passwordHash = $this->passwordHasher->hash(
            $command->password,
        );

        $this->credentials->savePasswordHash(
            userId: $userId,
            passwordHash: $passwordHash,
        );

        return null;
    }
}
