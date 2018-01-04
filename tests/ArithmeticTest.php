<?php

namespace Bakame\DiceRoller\Test\Modifier;

use Bakame\DiceRoller\Arithmetic;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Rollable;
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
     * @covers ::getTraceAsString
     */
    public function testGetter()
    {
        $cup = new Cup(new Dice(3), new Dice(3), new Dice(4));
        $obj = new Arithmetic($cup, Arithmetic::EXPONENTIATION, 3);

        $this->assertSame(Arithmetic::EXPONENTIATION, $obj->getOperator());
        $this->assertSame(3, $obj->getValue());
        $this->assertSame($cup, $obj->getRollable());
        $this->assertSame('(2D3+D4)^3', (string) $obj);
        $this->assertSame('', $obj->getTraceAsString());
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
     * @covers ::getTraceAsString
     * @covers \Bakame\DiceRoller\Cup::getTraceAsString
     */
    public function testGetTrace()
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will($this->returnValue(1));

        $dice->method('getTraceAsString')
            ->will($this->returnValue('1'))
        ;

        $rollables = new Cup($dice, clone $dice);
        $cup = new Arithmetic($rollables, '*', 3);
        $this->assertSame('', $rollables->getTraceAsString());
        $this->assertSame('', $cup->getTraceAsString());
        $this->assertSame(6, $cup->roll());
        $this->assertSame('(1 + 1) * 3', $cup->getTraceAsString());
        $this->assertSame('1 + 1', $rollables->getTraceAsString());
    }

    /**
     * @covers ::roll
     * @covers ::calculate
     * @covers ::exp
     * @covers ::getTraceAsString
     */
    public function testRollWithNegativeDiceValue()
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will($this->returnValue(-1));

        $dice->method('getTraceAsString')
            ->will($this->returnValue('-1'));
        ;

        $cup = new Arithmetic($dice, '^', 3);
        $this->assertSame(-1, $dice->roll());
        //$this->assertSame(-1, $cup->roll());
        $cup->roll();
        $this->assertSame('-1 ^ 3', $cup->getTraceAsString());
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
