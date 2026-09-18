<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\SetPassword;

use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Shared\Application\Bus\Command\Command;
use Tests\TestCase;

final class SetPasswordCommandTest extends TestCase
{
    public function test_it_is_a_command_and_exposes_password_data(): void
    {
        $command = new SetPasswordCommand(
            userId: '01JTESTUSERID00000000000000',
            password: 'secret-password',
        );

        self::assertInstanceOf(Command::class, $command);
        self::assertSame('01JTESTUSERID00000000000000', $command->userId);
        self::assertSame('secret-password', $command->password);
    }
}
