<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\ArithmeticModifier;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\ArithmeticModifier
 */
final class ArithmeticModifierTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testArithmeticModifierConstructorThrows1()
    {
        $this->expectException(OutOfRangeException::class);
        new ArithmeticModifier(new Dice(6), -3, '+');
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticModifierConstructorThrows2()
    {
        $this->expectException(OutOfRangeException::class);
        new ArithmeticModifier(new Dice(6), 3, '**');
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::sum
     * @covers ::roll
     * @dataProvider validArithmeticProvider
     */
    public function testArithmeticModifier(string $operator, int $size, int $value, int $min, int $max)
    {
        $roll = new ArithmeticModifier(new Dice($size), $value, $operator);
        $test = $roll->roll();
        $this->assertSame($min, $roll->getMinimum());
        $this->assertSame($max, $roll->getMaximum());
        $this->assertGreaterThanOrEqual($min, $test);
        $this->assertLessThanOrEqual($max, $test);
    }

    public function validArithmeticProvider()
    {
        return [
            'adding' => [
                'operator' => '+',
                'size' => 6,
                'value' => 10,
                'min' => 11,
                'max' => 16,
            ],
            'substracting' => [
                'operator' => '-',
                'size' => 6,
                'value' => 3,
                'min' => -2,
                'max' => 3,
            ],
            'multiplying' => [
                'operator' => '*',
                'size' => 6,
                'value' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'intdiv' => [
                'operator' => '/',
                'size' => 6,
                'value' => 2,
                'min' => 0,
                'max' => 3,
            ],
            'pow' => [
                'operator' => '^',
                'size' => 6,
                'value' => 2,
                'min' => 1,
                'max' => 36,
            ],
        ];
    }
}
