<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Sales\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;

final class MoneyTest extends TestCase
{
    public function test_it_creates_money(): void
    {
        $money = Money::of(15000, 'IRR');

        self::assertSame(15000, $money->amount());
        self::assertSame('IRR', $money->currency());
    }

    public function test_it_multiplies(): void
    {
        $line = Money::of(1000)->multiply(3);

        self::assertSame(3000, $line->amount());
    }

    public function test_it_adds_same_currency(): void
    {
        $sum = Money::of(1000)->add(Money::of(500));

        self::assertSame(1500, $sum->amount());
    }

    public function test_it_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::of(-1);
    }

    public function test_it_rejects_currency_mismatch_on_add(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::of(100, 'IRR')->add(Money::of(100, 'USD'));
    }
}
