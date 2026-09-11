<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Support;

use InvalidArgumentException;
use StoreYar\Shared\Support\Money\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_can_add_money_with_same_currency(): void
    {
        $first = new Money('100.25', 'IRR');
        $second = new Money('50.75', 'IRR');

        $result = $first->add($second);

        $this->assertSame('151.00000000', $result->amount());
        $this->assertSame('IRR', $result->currency());
    }

    public function test_it_can_subtract_money_with_same_currency(): void
    {
        $first = new Money('100.25', 'IRR');
        $second = new Money('40.10', 'IRR');

        $result = $first->subtract($second);

        $this->assertSame('60.15000000', $result->amount());
    }

    public function test_it_can_compare_money(): void
    {
        $first = new Money('100.00', 'IRR');
        $second = new Money('100.00000000', 'IRR');

        $this->assertTrue($first->equals($second));
    }

    public function test_it_can_detect_positive_zero_and_negative_values(): void
    {
        $positive = new Money('10.00', 'IRR');
        $zero = new Money('0', 'IRR');
        $negative = new Money('-10.00', 'IRR');

        $this->assertTrue($positive->isPositive());
        $this->assertTrue($zero->isZero());
        $this->assertTrue($negative->isNegative());
    }

    public function test_it_rejects_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $first = new Money('100', 'IRR');
        $second = new Money('100', 'USD');

        $first->add($second);
    }

    public function test_it_rejects_invalid_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money('100.50.20', 'IRR');
    }
}
