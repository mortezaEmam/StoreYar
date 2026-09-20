<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Queries\ValidateSession;

use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ValidateSessionHandler implements QueryHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private SessionTokenGenerator $tokenGenerator,
        private UserRepository $users,
        private Clock $clock,
    ) {}

    public function handle(Query $query): mixed
    {
        if (! $query instanceof ValidateSessionQuery) {
            throw new \InvalidArgumentException(
                'ValidateSessionHandler received an invalid query.',
            );
        }

        if ($query->token === '') {
            return null;
        }

        $tokenHash = $this->tokenGenerator->hash($query->token);

        $sessionId = $this->sessions->findActiveByTokenHash(
            tokenHash: $tokenHash,
            now: $this->clock->now(),
        );

        if ($sessionId === null) {
            return null;
        }

        return $sessionId;
    }
}
