<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Arithmetic;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Roll;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::validate
     */
    public function testArithmeticConstructorThrows1()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '+', -3);
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     */
    public function testArithmeticConstructorThrows2()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '**', 3);
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     */
    public function testArithmeticConstructorThrows3()
    {
        $this->expectException(Exception::class);
        new Arithmetic(new Dice(6), '/', 0);
    }

    /**
     * @covers ::__toString
     * @covers ::validate
     * @covers ::getOperator
     * @covers ::getValue
     * @covers ::getRollable
     */
    public function testGetter()
    {
        $cup = new Cup(new Dice(3), new Dice(3), new Dice(4));
        $obj = new Arithmetic($cup, Arithmetic::EXPONENTIATION, 3);

        $this->assertSame(Arithmetic::EXPONENTIATION, $obj->getOperator());
        $this->assertSame(3, $obj->getValue());
        $this->assertSame($cup, $obj->getRollable());
        $this->assertSame('(2D3+D4)^3', (string) $obj);
    }

    /**
     * @covers ::withRollable
     * @covers ::withValue
     * @covers ::withOperator
     */
    public function testImmutability()
    {
        $cup = new Cup(new Dice(3), new Dice(3), new Dice(4));
        $obj = new Arithmetic($cup, '^', 3);

        $this->assertSame($obj->withRollable($cup), $obj);
        $this->assertSame($obj->withValue(3), $obj);
        $this->assertSame($obj->withOperator(Arithmetic::EXPONENTIATION), $obj);
        $this->assertNotEquals($obj->withOperator(Arithmetic::ADDITION), $obj);
        $this->assertNotEquals($obj->withValue(4), $obj);
        $this->assertNotEquals($obj->withRollable(new Dice(3)), $obj);
    }

    /**
     * @covers ::roll
     * @covers ::calculate
     * @covers \Bakame\DiceRoller\Result
     * @covers ::exp
     */
    public function testRollWithNegativeDiceValue()
    {
        $dice = new CustomDice(-1, -1, -1);
        $cup = new Arithmetic($dice, '^', 3);
        $this->assertSame(-1, $cup->roll()->getResult());
        $this->assertSame(-1, $cup->getMaximum());
        $this->assertSame(-1, $cup->getMinimum());
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
     * @covers \Bakame\DiceRoller\Result
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
        $this->assertGreaterThanOrEqual($min, $test->getResult());
        $this->assertLessThanOrEqual($max, $test->getResult());
        $this->assertSame($test->getAnnotation(), (string) $roll);
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
