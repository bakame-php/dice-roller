<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\Dice
 */
final class DiceTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers \Bakame\DiceRoller\Result
     */
    public function testSixSidedValues()
    {
        $expected = 6;
        $dice = new Dice($expected);
        $this->assertCount($expected, $dice);
        $this->assertSame($expected, $dice->getMaximum());
        $this->assertSame(1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            $this->assertGreaterThanOrEqual($dice->getMinimum(), $test->getResult());
            $this->assertLessThanOrEqual($dice->getMaximum(), $test->getResult());
            $this->assertSame($test->getAnnotation(), (string) $dice);
        }
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorWithWrongValue()
    {
        $this->expectException(Exception::class);
        new Dice(1);
    }
}
