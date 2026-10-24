<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RequestPasswordReset;

use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordResetTokenRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;

final readonly class RequestPasswordResetHandler implements CommandHandler
{
    private const TTL_MINUTES = 60;

    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private PasswordResetNotifier $notifier,
    ) {}

    public function handle(Command $command): mixed
    {
        if (! $command instanceof RequestPasswordResetCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $email = strtolower(trim($command->email));
        $user = $this->users->findByEmail($email);

        // Always succeed from caller's perspective (no email enumeration).
        if ($user === null) {
            return null;
        }

        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $expires = new \DateTimeImmutable('+'.self::TTL_MINUTES.' minutes');

        $this->tokens->store($email, $hash, $expires);
        $this->notifier->notify($email, $plain);

        return null;
    }
}
