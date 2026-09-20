<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\AuthenticateUser;

use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final readonly class AuthenticateUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private UserCredentialRepository $credentials,
        private PasswordHasher $passwordHasher,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof AuthenticateUserCommand) {
            throw new \InvalidArgumentException(
                'AuthenticateUserHandler received an invalid command.',
            );
        }

        $user = $this->users->findByEmail($command->email);

        if ($user === null) {
            throw new \InvalidArgumentException(
                'Invalid credentials.',
            );
        }


        if (! $user->status()->isActive()) {
            throw new \InvalidArgumentException(
                'Invalid credentials.',
            );
        }
        

        $passwordHash = $this->credentials->findPasswordHash(
            UserId::fromString($user->id()),
        );

        if (
            $passwordHash === null
            || ! $this->passwordHasher->verify(
                $command->password,
                $passwordHash,
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid credentials.',
            );
        }

        return new AuthenticationResult(
            user: $user,
        );
    }
}
