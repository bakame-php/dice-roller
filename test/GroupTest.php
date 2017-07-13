<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Group;

final class GroupTest extends \PHPUnit\Framework\TestCase
{
    public function testFiveFourSidedDice()
    {
        $group = new Group(5, 4);

        for ($i = 0; $i < 1000; $i++) {
            $test = $group->roll();
            $this->assertGreaterThanOrEqual(5, $test);
            $this->assertLessThanOrEqual(20, $test);
        }
    }

    /**
     * @expectedException OutOfRangeException
     */
    public function testException()
    {
        new Group(-2, 6);
    }
}
