<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Dice;

final class DiceTest extends \PHPUnit\Framework\TestCase
{
    public function testSixSidedValues()
    {
        $dice = new Dice(6);

        for ($i = 0; $i < 1000; $i++) {
            $test = $dice->roll();
            $this->assertGreaterThanOrEqual(1, $test);
            $this->assertLessThanOrEqual(6, $test);
        }
    }

    /**
     * @expectedException OutOfRangeException
     */
    public function testConstructorWithWrongValue()
    {
        new Dice(1);
    }
}
