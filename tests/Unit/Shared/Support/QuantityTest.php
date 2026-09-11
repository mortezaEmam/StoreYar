<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Support;

use InvalidArgumentException;
use StoreYar\Shared\Support\Quantity\Quantity;
use Tests\TestCase;

class QuantityTest extends TestCase
{
    public function test_it_can_add_quantities(): void
    {
        $first = new Quantity('100.25');
        $second = new Quantity('50.75');

        $result = $first->add($second);

        $this->assertSame('151.00000000', $result->value());
    }

    public function test_it_can_subtract_quantities(): void
    {
        $first = new Quantity('100.25');
        $second = new Quantity('40.10');

        $result = $first->subtract($second);

        $this->assertSame('60.15000000', $result->value());
    }

    public function test_it_can_compare_quantities(): void
    {
        $first = new Quantity('100.00');
        $second = new Quantity('100.00000000');

        $this->assertTrue($first->equals($second));
    }

    public function test_it_can_detect_positive_and_zero_values(): void
    {
        $positive = new Quantity('10.00');
        $zero = new Quantity('0');

        $this->assertTrue($positive->isPositive());
        $this->assertTrue($zero->isZero());
        $this->assertFalse($positive->isNegative());
        $this->assertFalse($zero->isNegative());
    }

    public function test_it_rejects_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Quantity('-10');
    }

    public function test_it_rejects_subtraction_resulting_in_negative_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $first = new Quantity('10');
        $second = new Quantity('20');

        $first->subtract($second);
    }

    public function test_it_rejects_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Quantity('100.50.20');
    }
}
