<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Group;

final class CupTest extends \PHPUnit\Framework\TestCase
{
    public function testRoll()
    {
        $group1 = new Group(4, 10);
        $group2 = new Group(2, 4);

        $cup = new Cup();

        $cup->addGroup($group1);
        $cup->addGroup($group2);

        for ($i = 0; $i < 1000; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual(6, $test);
            $this->assertLessThanOrEqual(48, $test);
        }
    }
}
