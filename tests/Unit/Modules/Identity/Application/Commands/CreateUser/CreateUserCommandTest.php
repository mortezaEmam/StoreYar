<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\CreateUser;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Shared\Application\Bus\Command\Command;

final class CreateUserCommandTest extends TestCase
{
    public function test_it_is_a_command_and_exposes_creation_data(): void
    {
        $command = new CreateUserCommand(
            email: 'ali@example.com',
            name: 'Ali',
        );

        self::assertInstanceOf(Command::class, $command);
        self::assertSame('ali@example.com', $command->email);
        self::assertSame('Ali', $command->name);
    }
}
