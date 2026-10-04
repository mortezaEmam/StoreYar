<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\AuthenticateUser;

use App\Modules\Identity\Domain\Exceptions\InvalidAuthenticateUser;
use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Domain\Exceptions\InvalidCredentials;
use StoreYar\Shared\Domain\Contracts\Clock;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\Entities\Session;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final class AuthenticateUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private UserCredentialRepository $credentials,
        private PasswordHasher $passwordHasher,
        private SessionRepository $sessions,
        private SessionTokenGenerator $tokenGenerator,
        private Clock $clock,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof AuthenticateUserCommand) {
            new InvalidAuthenticateUser();

        }

        $user = $this->users->findByEmail($command->email);

        if ($user === null) {
            throw new InvalidCredentials();
        }

        if (! $user->status()->isActive()) {
            throw new InvalidCredentials();
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
            throw new InvalidCredentials();
        }

        $token = $this->tokenGenerator->generate();
        $tokenHash = $this->tokenGenerator->hash($token);

        $now = $this->clock->now();
        $expiresAt = $now->modify('+30 days');
        $sessionId = SessionId::generate();

        $session = Session::create(
            sessionId: $sessionId,
            userId: $user->id(),
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
        );

        $this->sessions->create(
            sessionId: $session->sessionId(),
            userId: $session->userId(),
            tokenHash: $session->tokenHash(),
            expiresAt: $session->expiresAt(),
        );

        return new AuthenticationResult(
            user: $user,
            session: $session,
            token: $token,
        );
    }
}
