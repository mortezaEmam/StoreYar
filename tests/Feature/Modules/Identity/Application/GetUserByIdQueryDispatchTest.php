<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Application;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use Tests\TestCase;

final class GetUserByIdQueryDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_user_by_id_query_is_dispatched_through_the_query_bus(): void
    {
        $userId = UserId::generate();

        $user = User::create(
            id: $userId,
            email: 'ali@example.com',
            name: 'Ali',
            now: new \DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        $this->app
            ->make(UserRepository::class)
            ->save($user);

        $result = $this->app
            ->make(QueryBus::class)
            ->ask(
                new GetUserByIdQuery(
                    userId: $userId->value(),
                ),
            );

        self::assertInstanceOf(User::class, $result);
        self::assertSame($userId->value(), $result->id());
        self::assertSame('ali@example.com', $result->email());
        self::assertSame('Ali', $result->name());
    }
}
