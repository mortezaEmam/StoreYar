<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Infrastructure\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelSessionTokenGenerator;
use Tests\TestCase;

final class LaravelSessionTokenGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_token_generator_is_bound_to_laravel_implementation(): void
    {
        $generator = $this->app->make(SessionTokenGenerator::class);

        self::assertInstanceOf(
            LaravelSessionTokenGenerator::class,
            $generator,
        );
    }

    public function test_generated_token_is_64_characters(): void
    {
        $generator = $this->app->make(SessionTokenGenerator::class);

        $token = $generator->generate();

        self::assertSame(64, strlen($token));
    }

    public function test_hash_is_deterministic_and_different_from_plain_token(): void
    {
        $generator = $this->app->make(SessionTokenGenerator::class);

        $token = $generator->generate();

        $hashOne = $generator->hash($token);
        $hashTwo = $generator->hash($token);

        self::assertSame($hashOne, $hashTwo);
        self::assertNotSame($token, $hashOne);
        self::assertSame(64, strlen($hashOne));
    }
}
