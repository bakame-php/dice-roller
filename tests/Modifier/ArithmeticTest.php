<?php

namespace Bakame\DiceRoller\Test\Modifier;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\Modifier\Arithmetic
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
     * @covers ::getTrace
     * @covers ::setTrace
     */
    public function testToString()
    {
        $cup = new Arithmetic(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), '^', 3);
        $this->assertSame('(2D3+D4)^3', (string) $cup);
        $this->assertSame('', $cup->getTrace());
    }

    /**
     * @covers ::roll
     * @covers ::getTrace
     * @covers ::setTrace
     * @covers \Bakame\DiceRoller\Cup::getTrace
     */
    public function testGetTrace()
    {
        $dice = new class() implements Rollable {
            public function getMinimum(): int
            {
                return 1;
            }

            public function getMaximum(): int
            {
                return 1;
            }

            public function roll(): int
            {
                return 1;
            }

            public function __toString()
            {
                return '1';
            }

            public function getTrace(): string
            {
                return '1';
            }
        };

        $rollables = new Cup($dice, clone $dice);
        $cup = new Arithmetic($rollables, '*', 3);
        $this->assertSame('', $rollables->getTrace());
        $this->assertSame('', $cup->getTrace());
        $this->assertSame(6, $cup->roll());
        $this->assertSame('(1 + 1) * 3', $cup->getTrace());
        $this->assertSame('1 + 1', $rollables->getTrace());
    }

    /**
     * @covers ::roll
     * @covers ::calculate
     * @covers ::exp
     * @covers ::getTrace
     */
    public function testRollWithNegativeDiceValue()
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will($this->returnValue(-1));

        $dice->method('getTrace')
            ->will($this->returnValue('-1'));
        ;

        $cup = new Arithmetic($dice, '^', 3);
        $this->assertSame(-1, $dice->roll());
        //$this->assertSame(-1, $cup->roll());
        $cup->roll();
        $this->assertSame('-1 ^ 3', $cup->getTrace());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::roll
     * @covers ::calculate
     * @covers ::multiply
     * @covers ::add
     * @covers ::subs
     * @covers ::div
     * @covers ::exp
     * @dataProvider validParametersProvider
     * @param string $operator
     * @param int    $size
     * @param int    $value
     * @param int    $min
     * @param int    $max
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
