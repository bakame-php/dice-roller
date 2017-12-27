<?php

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\FudgeDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Bakame\DiceRoller\FudgeDice
 */
final class FudgeDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::getTrace
     */
    public function testFudgeDice()
    {
        $dice = new FudgeDice();
        $this->assertCount(3, $dice);
        $this->assertSame(1, $dice->getMaximum());
        $this->assertSame(-1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            $this->assertContains($dice->getTrace(), ['-1', '0', '1']);
            $this->assertGreaterThanOrEqual($dice->getMinimum(), $test);
            $this->assertLessThanOrEqual($dice->getMaximum(), $test);
            $this->assertSame('', $dice->getTrace());
        }
    }
}
