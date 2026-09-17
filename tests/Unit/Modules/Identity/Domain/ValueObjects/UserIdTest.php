<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;

final class UserIdTest extends TestCase
{
    public function test_it_generates_a_valid_ulid(): void
    {
        $id = UserId::generate();

        $this->assertSame(26, strlen($id->value()));
        $this->assertSame($id->value(), (string) $id);
    }

    public function test_it_can_be_created_from_a_valid_ulid(): void
    {
        $id = UserId::generate();
        $restored = UserId::fromString($id->value());

        $this->assertTrue($id->equals($restored));
    }

    public function test_it_rejects_an_invalid_ulid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserId::fromString('invalid-user-id');
    }

    public function test_two_different_generated_ids_are_not_equal(): void
    {
        $first = UserId::generate();
        $second = UserId::generate();

        $this->assertFalse($first->equals($second));
    }
}
