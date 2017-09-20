<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\FudgeDice;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\FudgeDice
 */
final class FudgeDiceTest extends TestCase
{
    /**
     * @covers ::count
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     */
    public function testFudgeDice()
    {
        $dice = new FudgeDice();
        $this->assertCount(3, $dice);
        $this->assertSame(1, $dice->getMaximum());
        $this->assertSame(-1, $dice->getMinimum());
        for ($i = 0; $i < 10; $i++) {
            $test = $dice->roll();
            $this->assertGreaterThanOrEqual($dice->getMinimum(), $test);
            $this->assertLessThanOrEqual($dice->getMaximum(), $test);
        }
    }
}
