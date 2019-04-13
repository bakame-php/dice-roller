<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\Factory;
use Bakame\DiceRoller\SidedDie;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @coversDefaultClass Bakame\DiceRoller\Factory
 */
final class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new Factory();
    }

    /**
     * @covers ::__construct
     * @covers ::newInstance
     * @covers \Bakame\DiceRoller\ExpressionParser
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers ::decorate
     * @covers ::createDice
     * @dataProvider invalidStringProvider
     */
    public function testInvalidGroupDefinition(string $expected): void
    {
        self::expectException(CanNotBeRolled::class);
        $this->factory->newInstance($expected);
    }

    public function invalidStringProvider(): iterable
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
            'invalid custom dice' => ['3dss'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers \Bakame\DiceRoller\ExpressionParser
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers ::flattenRollable
     * @covers ::decorate
     * @covers ::createDice
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::toString
     * @covers \Bakame\DiceRoller\SidedDie::toString
     * @covers \Bakame\DiceRoller\FudgeDie::toString
     * @covers \Bakame\DiceRoller\Modifier\Arithmetic::toString
     * @covers \Bakame\DiceRoller\Modifier\DropKeep::toString
     * @covers \Bakame\DiceRoller\Modifier\Explode::toString
     * @dataProvider validStringProvider
     */
    public function testValidParser(string $expected, string $toString): void
    {
        $cup = $this->factory->newInstance($expected);
        self::assertSame($toString, $cup->toString());
    }

    public function validStringProvider(): iterable
    {
        return [
            'empty cup' => ['', '0'],
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
            'percentile dice' => ['3d%', '3D%'],
            'custom dice' => ['2d[1,2,34]', '2D[1,2,34]'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers ::create
     * @covers \Bakame\DiceRoller\ExpressionParser
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers ::createDice
     * @covers ::decorate
     * @dataProvider permissiveParserProvider
     */
    public function testPermissiveParser(string $full, string $short): void
    {
        self::assertEquals($this->factory->newInstance($full), $this->factory->newInstance($short));
    }

    public function permissiveParserProvider(): iterable
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
            'default percentile dice size' => [
                'full' => '1d%',
                'short' => 'd%',
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
            ],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::roll
     */
    public function testRollWithNoDice(): void
    {
        $cup = $this->factory->newInstance('');
        self::assertSame(0, $cup->minimum());
        self::assertSame(0, $cup->maximum());
        for ($i = 0; $i < 5; $i++) {
            self::assertEquals(0, $cup->roll());
        }
    }

    /**
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::getIterator
     * @covers \Bakame\DiceRoller\SidedDie::getSize
     */
    public function testRollWithSingleDice(): void
    {
        $dice = $this->factory->newInstance('d8');
        self::assertInstanceOf(SidedDie::class, $dice);
        self::assertSame(8, $dice->getSize());

        for ($i = 0; $i < 5; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }

    /**
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::getIterator
     * @covers \Bakame\DiceRoller\SidedDie::getSize
     */
    public function testRollWithDefaultDice(): void
    {
        $dice = $this->factory->newInstance('d');
        self::assertInstanceOf(SidedDie::class, $dice);
        self::assertSame(6, $dice->getSize());
        self::assertSame(1, $dice->minimum());
        self::assertSame(6, $dice->maximum());

        for ($i = 0; $i < 5; $i++) {
            $test = $dice->roll();
            self::assertGreaterThanOrEqual($dice->minimum(), $test);
            self::assertLessThanOrEqual($dice->maximum(), $test);
        }
    }

    /**
     * @covers ::newInstance
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::getIterator
     * @covers \Bakame\DiceRoller\SidedDie::getSize
     */
    public function testRollWithMultipleDice(): void
    {
        $cup = $this->factory->newInstance('2D6+3d4');
        self::assertInstanceOf(Traversable::class, $cup);
        self::assertCount(2, $cup);
        $res = iterator_to_array($cup, false);
        self::assertInstanceOf(Cup::class, $res[0]);
        self::assertCount(2, $res[0]);
        foreach ($res[0] as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(6, $dice->getSize());
        }

        self::assertCount(3, $res[1]);
        foreach ($res[1] as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(4, $dice->getSize());
        }

        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            self::assertGreaterThanOrEqual($cup->minimum(), $test);
            self::assertLessThanOrEqual($cup->maximum(), $test);
        }
    }
}
