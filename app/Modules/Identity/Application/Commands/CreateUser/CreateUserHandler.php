<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\CreateUser;

use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final readonly class CreateUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): User
    {
        if (! $command instanceof CreateUserCommand) {
            throw new \InvalidArgumentException(
                'CreateUserHandler received an invalid command.',
            );
        }

        $user = User::create(
            id: UserId::generate(),
            email: $command->email,
            name: $command->name,
            now: $this->clock->now(),
        );

        $this->users->save($user);

        return $user;
    }
}
