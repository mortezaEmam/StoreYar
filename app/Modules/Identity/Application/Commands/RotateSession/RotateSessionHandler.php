<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RotateSession;

use App\Modules\Identity\Domain\Exceptions\InvalidRotateSession;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Identity\Application\Results\SessionRotationResult;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Entities\Session;
use StoreYar\Modules\Identity\Domain\Exceptions\InvalidSession;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class RotateSessionHandler implements CommandHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private SessionTokenGenerator $tokenGenerator,
        private Clock $clock,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof RotateSessionCommand) {
            new InvalidRotateSession();
        }

        $now = $this->clock->now();

        $currentSession = $this->sessions->findById(
            $command->currentSessionId,
        );

        if (
            $currentSession === null
            || ! $currentSession->isActive($now)
        ) {
            throw new InvalidSession();
        }

        $token = $this->tokenGenerator->generate();
        $tokenHash = $this->tokenGenerator->hash($token);

        $newSession = Session::create(
            sessionId: SessionId::generate(),
            userId: $currentSession->userId(),
            tokenHash: $tokenHash,
            expiresAt: $now->modify('+30 days'),
        );

        DB::transaction(function () use ($newSession, $currentSession): void {
            $this->sessions->create(
                sessionId: $newSession->sessionId(),
                userId: $newSession->userId(),
                tokenHash: $newSession->tokenHash(),
                expiresAt: $newSession->expiresAt(),
            );

            $this->sessions->revoke(
                $currentSession->sessionId(),
            );
        });

        return new SessionRotationResult(
            session: $newSession,
            token: $token,
        );
    }
}
