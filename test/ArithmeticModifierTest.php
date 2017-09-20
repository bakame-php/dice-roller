<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\ArithmeticModifier;
use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
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
        $this->expectException(Exception::class);
        new ArithmeticModifier(new Dice(6), -3, '+');
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticModifierConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new ArithmeticModifier(new Dice(6), 3, '**');
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $cup = new ArithmeticModifier(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), 3, '^');
        $this->assertSame('(2D3+D4)^3', (string) $cup);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
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
