<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Infrastructure\Security;

use Illuminate\Support\Facades\Hash;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use Tests\TestCase;

final class LaravelPasswordHasherTest extends TestCase
{
    public function test_it_hashes_and_verifies_password(): void
    {
        $hasher = new LaravelPasswordHasher();

        $hashed = $hasher->hash('secret-password');

        $this->assertNotSame(
            'secret-password',
            $hashed,
        );

        $this->assertTrue(
            $hasher->verify(
                'secret-password',
                $hashed,
            ),
        );

        $this->assertFalse(
            $hasher->verify(
                'wrong-password',
                $hashed,
            ),
        );
    }

    public function test_it_uses_laravel_hashing_facade(): void
    {
        Hash::shouldReceive('make')
            ->once()
            ->with('secret-password')
            ->andReturn('hashed-password');

        Hash::shouldReceive('check')
            ->once()
            ->with('secret-password', 'hashed-password')
            ->andReturnTrue();

        $hasher = new LaravelPasswordHasher();

        $this->assertSame(
            'hashed-password',
            $hasher->hash('secret-password'),
        );

        $this->assertTrue(
            $hasher->verify(
                'secret-password',
                'hashed-password',
            ),
        );
    }
}
