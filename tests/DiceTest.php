<?php

namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Dice
 */
final class DiceTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::explain
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
            $this->assertGreaterThanOrEqual($dice->getMinimum(), $test);
            $this->assertLessThanOrEqual($dice->getMaximum(), $test);
            $this->assertContains($dice->explain(), ['1', '2', '3', '4', '5', '6']);
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
