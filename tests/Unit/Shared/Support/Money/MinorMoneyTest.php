<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Support\Money;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Support\Money\MinorMoney;

final class MinorMoneyTest extends TestCase
{
    public function test_it_creates_and_adds(): void
    {
        $sum = MinorMoney::of(1000, 'IRR')->add(MinorMoney::of(500, 'IRR'));
        self::assertSame(1500, $sum->amount());
        self::assertSame('IRR', $sum->currency());
    }

    public function test_it_multiplies(): void
    {
        self::assertSame(3000, MinorMoney::of(1000)->multiply(3)->amount());
    }

    public function test_it_rejects_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MinorMoney::of(-1);
    }
}
