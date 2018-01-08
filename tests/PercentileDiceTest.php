<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\PercentileDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\PercentileDice
 */
final class PercentileDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::__toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers \Bakame\DiceRoller\Result
     */
    public function testFudgeDice()
    {
        $dice = new PercentileDice();
        $this->assertCount(100, $dice);
        $this->assertSame(100, $dice->getMaximum());
        $this->assertSame(1, $dice->getMinimum());
        $this->assertSame('D%', (string) $dice);
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll()->getResult();
            $this->assertGreaterThanOrEqual($dice->getMinimum(), $test);
            $this->assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
