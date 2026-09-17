<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Contracts;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;

final class PasswordHasherTest extends TestCase
{
    public function test_contract_can_be_implemented(): void
    {
        $hasher = new TestPasswordHasher();

        $hashed = $hasher->hash('secret');

        $this->assertNotSame('secret', $hashed);
        $this->assertTrue(
            $hasher->verify('secret', $hashed),
        );
        $this->assertFalse(
            $hasher->verify('wrong', $hashed),
        );
    }
}

final class TestPasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:' . $plainPassword;
    }

    public function verify(
        string $plainPassword,
        string $hashedPassword,
    ): bool {
        return $hashedPassword === 'hashed:' . $plainPassword;
    }
}
