<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Application;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use Tests\TestCase;

final class CreateUserCommandDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_command_is_dispatched_through_the_command_bus(): void
    {
        $command = new CreateUserCommand(
            email: 'ali@example.com',
            name: 'Ali',
        );

        $user = $this->app
            ->make(CommandBus::class)
            ->dispatch($command);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('ali@example.com', $user->email());
        self::assertSame('Ali', $user->name());

        $this->assertDatabaseHas('identity_users', [
            'id' => $user->id(),
            'email' => 'ali@example.com',
            'name' => 'Ali',
            'status' => 'active',
            'version' => 0,
        ]);
    }
}
