<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Id;

use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Tests\TestCase;

class UlidGeneratorTest extends TestCase
{
    public function test_it_generates_a_valid_ulid(): void
    {
        $generator = new UlidGenerator();

        $id = $generator->generate();

        $this->assertIsString($id);
        $this->assertSame(26, strlen($id));
        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            $id
        );
    }

    public function test_it_generates_unique_ids(): void
    {
        $generator = new UlidGenerator();

        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertNotSame($first, $second);
    }
}
