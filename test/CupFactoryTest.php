<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\CupFactory;

final class CupFactoryTest extends \PHPUnit\Framework\TestCase
{
    private $cupFactory;

    public function setUp()
    {
        $this->cupFactory = new CupFactory();
    }

    public function testInstanceNoParam()
    {
        $this->assertInstanceOf(Cup::class, $this->cupFactory->newInstance());
    }

    public function testInstanceOneGroup()
    {
        $this->assertInstanceOf(Cup::class, $this->cupFactory->newInstance('2D6'));
    }

    public function testInstanceMultipleGroups()
    {
        $this->assertInstanceOf(Cup::class, $this->cupFactory->newInstance('2D6+3D4'));
    }

    public function testRollWithSingleDice()
    {
        $cup = $this->cupFactory->newInstance('D8');

        for ($i = 0; $i < 1000; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual(1, $test);
            $this->assertLessThanOrEqual(8, $test);
        }
    }

    public function testRollWithMultipleDice()
    {
        $cup = $this->cupFactory->newInstance('2D6+3D4');

        for ($i = 0; $i < 1000; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual(5, $test);
            $this->assertLessThanOrEqual(24, $test);
        }
    }
}
