<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Queries\GetUserById;

use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final readonly class GetUserByIdHandler implements QueryHandler
{
    public function __construct(
        private UserRepository $users,
    ) {}

    public function handle(Query $query): ?User
    {
        if (! $query instanceof GetUserByIdQuery) {
            throw new \InvalidArgumentException(
                'GetUserByIdHandler received an invalid query.',
            );
        }

        return $this->users->findById(
            UserId::fromString($query->userId),
        );
    }
}
