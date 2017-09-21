<?php
namespace Ethtezahl\DiceRoller\Test;

use Ethtezahl\DiceRoller\Cup;
use Ethtezahl\DiceRoller\Dice;
use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Factory;
use Ethtezahl\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use function Ethtezahl\DiceRoller\roll_create;

/**
 * @coversDefaultClass Ethtezahl\DiceRoller\Factory
 */
final class FactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory();
    }

    /**
     * @covers ::newInstance
     * @covers ::explode
     * @covers ::parsePool
     * @covers ::addArithmetic
     * @covers ::addExplode
     * @covers ::addDropKeep
     * @covers ::addComplexModifier
     * @covers ::createSimplePool
     * @covers ::createComplexPool
     * @dataProvider invalidStringProvider
     */
    public function testInvalidGroupDefinition(string $expected)
    {
        $this->expectException(Exception::class);
        roll_create($expected);
    }

    public function invalidStringProvider()
    {
        return [
            'missing separator D' => ['ZZZ'],
            'missing group definition' => ['+'],
            'invalid group' => ['10+3dF'],
            'invalid modifier' => ['3dFZZZZ'],
            'invalid explode modifier' => ['D6!>'],
            'invalid complex cup' => ['(3DF+2D6)*3+3F^2'],
            'invalid complex cup 2' => ['(3DFoobar+2D6)*3+3DF^2'],
            'invalid complex cup 3' => ['()*3'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers ::explode
     * @covers ::parsePool
     * @covers ::addArithmetic
     * @covers ::addExplode
     * @covers ::addDropKeep
     * @covers ::addComplexModifier
     * @covers ::createSimplePool
     * @covers ::createComplexPool
     * @covers \Ethtezahl\DiceRoller\roll_create
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::__toString
     * @covers \Ethtezahl\DiceRoller\Dice::__toString
     * @covers \Ethtezahl\DiceRoller\FudgeDice::__toString
     * @covers \Ethtezahl\DiceRoller\Modifier\Arithmetic::__toString
     * @covers \Ethtezahl\DiceRoller\Modifier\DropKeep::__toString
     * @covers \Ethtezahl\DiceRoller\Modifier\Explode::__toString
     * @dataProvider validStringProvider
     */
    public function testValidParser(string $expected, string $toString)
    {
        $cup = roll_create($expected);
        $this->assertInstanceOf(Rollable::class, $cup);
        $this->assertSame($toString, (string) $cup);
    }

    public function validStringProvider()
    {
        return [
            'empty cup' => ['', ''],
            'simple' => ['2D3', '2D3'],
            'empty nb dice' => ['d3', 'D3'],
            'empty nb sides' => ['3d', '3D6'],
            'mixed group' => ['2D3+1D4', '2D3+D4'],
            'case insensitive' => ['2d3+1d4', '2D3+D4'],
            'default to one dice' => ['d3+d4+1d3+5d2', '2D3+D4+5D2'],
            'fudge dice' => ['2dF', '2DF'],
            'multiple fudge dice' => ['dF+3dF', 'DF+3DF'],
            'mixed cup' => ['2df+3d2', '2DF+3D2'],
            'add modifier' => ['2d3-4', '2D3-4'],
            'add modifier to multiple group' => ['2d3+4+3dF!>1/4^3', '2D3+4+3DF!>1/4^3'],
            'add explode modifier' => ['2d3!', '2D3!'],
            'add keep lowest modifier' => ['2d3kl1', '2D3KL1'],
            'add keep highest modifier' => ['2d3kh2', '2D3KH2'],
            'add drop lowest modifier' => ['4d6dl2',  '4D6DL2'],
            'add drop highest modifier' => ['4d6dh3', '4D6DH3'],
            'complex mixed cup' => ['(3DF+2D6)*3+3DF^2', '(3DF+2D6)*3+3DF^2'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers ::explode
     * @covers ::parsePool
     * @covers ::addArithmetic
     * @covers ::addExplode
     * @covers ::addDropKeep
     * @covers ::addComplexModifier
     * @dataProvider permissiveParserProvider
     */
    public function testPermissiveParser($full, $short)
    {
        $this->assertEquals(
            roll_create($full),
            $this->factory->newInstance($short)
        );
    }

    public function permissiveParserProvider()
    {
        return [
            'default dice size' => [
                'full' => '1d6',
                'short' => '1d',
            ],
            'default dice size 2' => [
                'full' => '1d6',
                'short' => 'd',
            ],
            'default fudge dice size' => [
                'full' => '1dF',
                'short' => 'df',
            ],
            'default keep lowest modifier' => [
                'full' => '2d3kl1',
                'short' => '2d3KL',
            ],
            'default keep highest modifier' => [
                'full' => '2d3KH1',
                'short' => '2d3kh',
            ],
            'default drop highest modifier' => [
                'full' => '2d3dh1',
                'short' => '2d3DH',
            ],
            'default drop lowest modifier' => [
                'full' => '2d3dl1',
                'short' => '2D3Dl',
            ],
            'default explode modifier' => [
                'full' => '1d6!',
                'short' => 'D!',
            ],
            'default explode modifier with threshold' => [
                'full' => '1d6!=3',
                'short' => 'D!3',
            ]
        ];
    }

    /**
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     */
    public function testFiveFourSidedDice()
    {
        $group = $this->factory->newInstance('5D4');
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
        $cup = $this->factory->newInstance();
        $this->assertCount(0, $cup);
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals(0, $cup->roll());
        }
    }

    /**
     * @covers ::parsePool
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithSingleDice()
    {
        $cup = $this->factory->newInstance('d8');
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
     * @covers ::parsePool
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithDefaultDice()
    {
        $cup = $this->factory->newInstance('d');
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
     * @covers ::parsePool
     * @covers \Ethtezahl\DiceRoller\Cup::count
     * @covers \Ethtezahl\DiceRoller\Cup::getIterator
     * @covers \Ethtezahl\DiceRoller\Rollable
     * @covers \Ethtezahl\DiceRoller\Dice::count
     */
    public function testRollWithMultipleDice()
    {
        $cup = $this->factory->newInstance('2D6+3d4');
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
