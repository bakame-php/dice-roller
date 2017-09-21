<?php
namespace Ethtezahl\DiceRoller\Test\Modifier;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Modifier\Arithmetic;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Modifier\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows1()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '+', -3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '**', 3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows3()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '/', 0);
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $cup = new Arithmetic(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), '^', 3);
        $this->assertSame('(2D3+D4)^3', (string) $cup);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::roll
     * @dataProvider validParametersProvider
     */
    public function testArithmetic(string $operator, int $size, int $value, int $min, int $max)
    {
        $roll = new Arithmetic(new Dice($size), $operator, $value);
        $test = $roll->roll();
        $this->assertSame($min, $roll->getMinimum());
        $this->assertSame($max, $roll->getMaximum());
        $this->assertGreaterThanOrEqual($min, $test);
        $this->assertLessThanOrEqual($max, $test);
    }

    public function validParametersProvider()
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
