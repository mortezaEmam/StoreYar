<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\ResetPassword;

use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordResetTokenRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\Exceptions\InvalidCredentials;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final readonly class ResetPasswordHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private PasswordHasher $passwordHasher,
        private UserCredentialRepository $credentials,
        private SessionRepository $sessions,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof ResetPasswordCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $email = strtolower(trim($command->email));
        $user = $this->users->findByEmail($email);
        $storedHash = $this->tokens->findHashByEmail($email);

        if ($user === null || $storedHash === null) {
            throw new InvalidCredentials();
        }

        $incoming = hash('sha256', $command->token);
        if (! hash_equals($storedHash, $incoming)) {
            throw new InvalidCredentials();
        }

        $passwordHash = $this->passwordHasher->hash($command->password);
        $this->credentials->savePasswordHash($user->userId(), $passwordHash);
        $this->tokens->deleteByEmail($email);

        // Direct session revoke — avoid nested CommandBus dispatch
        $this->sessions->revokeAllForUser($user->userId()->value());

        return null;
    }
}
