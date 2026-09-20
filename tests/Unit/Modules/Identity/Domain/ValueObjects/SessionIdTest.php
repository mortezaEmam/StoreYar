<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;

final class SessionIdTest extends TestCase
{
    public function test_it_generates_a_valid_ulid(): void
    {
        $sessionId = SessionId::generate();

        self::assertTrue(
            \Illuminate\Support\Str::isUlid($sessionId->value()),
        );
    }

    public function test_it_reconstitutes_from_a_valid_ulid(): void
    {
        $original = SessionId::generate();

        $reconstituted = SessionId::fromString(
            $original->value(),
        );

        self::assertTrue(
            $original->equals($reconstituted),
        );

        self::assertSame(
            $original->value(),
            (string) $reconstituted,
        );
    }

    public function test_it_rejects_an_invalid_ulid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Session ID must be a valid ULID.',
        );

        SessionId::fromString('invalid-session-id');
    }
}
