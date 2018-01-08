<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\CustomDice
 */
final class CustomDiceTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::__toString
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers \Bakame\DiceRoller\Result
     */
    public function testFudgeDice()
    {
        $dice = new CustomDice(1, 2, 2, 4, 4);
        $this->assertCount(5, $dice);
        $this->assertSame(4, $dice->getMaximum());
        $this->assertSame(1, $dice->getMinimum());
        $this->assertSame('D[1,2,2,4,4]', (string) $dice);
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
        new CustomDice(1);
    }
}
