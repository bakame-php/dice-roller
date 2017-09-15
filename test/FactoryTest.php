<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Rollable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Factory
 */
final class FactoryTest extends TestCase
{
    private $Factory;

    public function setUp()
    {
        $this->Factory = new Factory();
    }

    public function testConstructor()
    {
        $this->assertEquals($this->Factory, new Factory());
    }

    /**
     * @covers ::newInstance
     * @covers ::explode
     * @covers ::parseGroup
     * @covers ::addModifier
     * @dataProvider invalidStringProvider
     */
    public function testInvalidGroupDefinition(string $expected)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Factory->newInstance($expected);
    }

    public function invalidStringProvider()
    {
        return [
            'missing separator D' => ['ZZZ'],
            'missing group definition' => ['+'],
            'invalid group' => ['10+3dF'],
            'invalid modifier' => ['3dFZZZZ'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers ::explode
     * @covers ::parseGroup
     * @covers ::addModifier
     * @covers ::createDicePool
     * @covers ::createFudgeDicePool
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @dataProvider validStringProvider
     */
    public function testValidGroupDefinition(string $expected)
    {
        $cup = $this->Factory->newInstance($expected);
        $this->assertInstanceOf(Rollable::class, $cup);
    }

    public function validStringProvider()
    {
        return [
            'empty cup' => [''],
            'simple' => ['2D3'],
            'empty nb dice' => ['d3'],
            'empty nb sides' => ['3d'],
            'mixed group' => ['2D3+1D4'],
            'case insensitive' => ['2d3+1d4'],
            'default to one dice' => ['d3+d4+1d3+5d2'],
            'fudge dice' => ['2dF'],
            'multiple fudge dice' => ['dF+3dF'],
            'mixed cup' => ['2df+3d2'],
            'add modifier' => ['2d3-4'],
            'add modifier to multiple group' => ['2d3+4+3dF!>1'],
            'add keep lowest modifier' => ['2d3kl1'],
            'add keep highest modifier' => ['2d3kh2'],
            'add drop lowest modifier' => ['4d6dl2'],
            'add drop highest modifier' => ['4d6dh3'],
        ];
    }

    /**
     * @covers ::createDicePool
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     */
    public function testFiveFourSidedDice()
    {
        $group = $this->Factory->newInstance('5D4');
        $this->assertCount(5, $group);
        $this->assertContainsOnlyInstancesOf(Dice::class, $group);
        foreach ($group as $dice) {
            $this->assertCount(4, $dice);
        }

        for ($i = 0; $i < 5; $i++) {
            $test = $group->roll();
            $this->assertGreaterThanOrEqual($group->getMinimum(), $test);
            $this->assertLessThanOrEqual($group->getMaximum(), $test);
        }
    }

    /**
     * @covers ::newInstance
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::roll
     */
    public function testRollWithNoDice()
    {
        $cup = $this->Factory->newInstance();
        $this->assertCount(0, $cup);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(0, $cup->roll());
        }
    }

    /**
     * @covers ::parseGroup
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithSingleDice()
    {
        $cup = $this->Factory->newInstance('d8');
        $this->assertCount(1, $cup);
        $this->assertContainsOnlyInstancesOf(Rollable::class, $cup);
        foreach ($cup as $dice) {
            $this->assertInstanceOf(Dice::class, $dice);
            $this->assertCount(8, $dice);
        }
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual($cup->getMinimum(), $test);
            $this->assertLessThanOrEqual($cup->getMaximum(), $test);
        }
    }

    /**
     * @covers ::parseGroup
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithDefaultDice()
    {
        $cup = $this->Factory->newInstance('d');
        $this->assertCount(1, $cup);
        $this->assertContainsOnlyInstancesOf(Rollable::class, $cup);
        foreach ($cup as $dice) {
            $this->assertInstanceOf(Dice::class, $dice);
            $this->assertCount(6, $dice);
            $this->assertSame(1, $dice->getMinimum());
            $this->assertSame(6, $dice->getMaximum());
        }

        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual($cup->getMinimum(), $test);
            $this->assertLessThanOrEqual($cup->getMaximum(), $test);
        }
    }

    /**
     * @covers ::newInstance
     * @covers ::parseGroup
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithMultipleDice()
    {
        $cup = $this->Factory->newInstance('2D6+3d4');
        $this->assertCount(2, $cup);
        $res = iterator_to_array($cup, false);
        $this->assertInstanceOf(Cup::class, $res[0]);
        $this->assertCount(2, $res[0]);
        foreach($res[0] as $dice) {
            $this->assertInstanceOf(Dice::class, $dice);
            $this->assertCount(6, $dice);
        }

        $this->assertCount(3, $res[1]);
        foreach($res[1] as $dice) {
            $this->assertInstanceOf(Dice::class, $dice);
            $this->assertCount(4, $dice);
        }

        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            $this->assertGreaterThanOrEqual($cup->getMinimum(), $test);
            $this->assertLessThanOrEqual($cup->getMaximum(), $test);
        }
    }
}
