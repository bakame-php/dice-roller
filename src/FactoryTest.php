<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Contract\Parser;
use Bakame\DiceRoller\Contract\RandomIntGenerator;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Exception\SyntaxError;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Factory
 */
final class FactoryTest extends TestCase
{
    private Factory $factory;

    public function setUp(): void
    {
        $this->factory = new Factory();
    }

    /**
     * @covers ::__construct
     * @covers ::newInstance
     * @covers \Bakame\DiceRoller\Exception\SyntaxError
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers ::decorate
     * @covers ::createDice
     * @dataProvider invalidStringProvider
     */
    public function testInvalidGroupDefinition(string $expected): void
    {
        self::expectException(SyntaxError::class);

        $this->factory->newInstance($expected);
    }

    public function invalidStringProvider(): iterable
    {
        return [
            'invalid explode modifier' => ['D6!>'],
        ];
    }

    /**
     * @covers ::newInstance
     * @covers ::create
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers ::flattenRollable
     * @covers ::decorate
     * @covers \Bakame\DiceRoller\Modifier\Arithmetic::fromOperation
     * @covers \Bakame\DiceRoller\Modifier\DropKeep::fromAlgorithm
     * @covers \Bakame\DiceRoller\Modifier\Explode::fromAlgorithm
     * @covers ::createDice
     * @dataProvider validStringProvider
     */
    public function testValidParser(string $expected, string $toString): void
    {
        $cup = $this->factory->newInstance($expected);
        self::assertSame($toString, $cup->notation());
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
            'add explode lesser than modifier' => ['2d3!<2', '2D3!<2'],
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
     */
    public function testRollWithNoDice(): void
    {
        $cup = $this->factory->newInstance('');
        self::assertSame(0, $cup->minimum());
        self::assertSame(0, $cup->maximum());
        for ($i = 0; $i < 5; $i++) {
            self::assertEquals(0, $cup->roll()->value());
        }
    }

    /**
     * @covers ::create
     * @covers ::addRollable
     * @covers ::createRollable
     */
    public function testRollWithSingleDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 3;
            }
        };

        $dice = $this->factory->newInstance('d8', $randomIntProvider, null);
        self::assertInstanceOf(SidedDie::class, $dice);
        self::assertSame(8, $dice->size());

        $test = $dice->roll()->value();
        self::assertSame(3, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }

    /**
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::getIterator
     * @covers \Bakame\DiceRoller\Dice\SidedDie::size
     */
    public function testRollWithDefaultDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 5;
            }
        };

        $dice = $this->factory->newInstance('d', $randomIntProvider, null);
        self::assertInstanceOf(SidedDie::class, $dice);
        self::assertSame(6, $dice->size());
        self::assertSame(1, $dice->minimum());
        self::assertSame(6, $dice->maximum());

        $test = $dice->roll()->value();

        self::assertSame(5, $test);
        self::assertGreaterThanOrEqual($dice->minimum(), $test);
        self::assertLessThanOrEqual($dice->maximum(), $test);
    }

    /**
     * @covers ::newInstance
     * @covers ::addRollable
     * @covers ::createRollable
     * @covers \Bakame\DiceRoller\Cup::count
     * @covers \Bakame\DiceRoller\Cup::getIterator
     * @covers \Bakame\DiceRoller\Dice\SidedDie::size
     */
    public function testRollWithMultipleDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 2;
            }
        };

        $cup = $this->factory->newInstance('2D6+3d4', $randomIntProvider, null);
        self::assertInstanceOf(Traversable::class, $cup);
        self::assertCount(2, $cup);
        $res = iterator_to_array($cup, false);
        self::assertInstanceOf(Cup::class, $res[0]);
        self::assertCount(2, $res[0]);
        foreach ($res[0] as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(6, $dice->size());
        }

        self::assertCount(3, $res[1]);
        foreach ($res[1] as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(4, $dice->size());
        }

        $result = $cup->roll()->value();
        self::assertSame(10, $result);
        self::assertGreaterThanOrEqual($cup->minimum(), $result);
        self::assertLessThanOrEqual($cup->maximum(), $result);
    }

    public function testInvalidArithmeticOperator(): void
    {
        $parser = new class() implements Parser {
            public function parse(string $notation): array
            {
                return [[
                    'definition' => [
                        'simple' => ['type' => 'D6', 'quantity' => '4'],
                    ],
                    'modifiers' => [
                        ['modifier' => 'arithmetic', 'operator' => '%', 'value' => 2],
                    ],
                ]];
            }
        };

        self::expectException(SyntaxError::class);

        (new Factory($parser))->newInstance('test');
    }

    public function testInvalidDropKeepOperator(): void
    {
        $parser = new class() implements Parser {
            public function parse(string $notation): array
            {
                return [[
                    'definition' => [
                        'simple' => ['type' => 'D6', 'quantity' => '4'],
                    ],
                    'modifiers' => [
                        ['modifier' => 'dropkeep', 'operator' => 'DV', 'value' => 2],
                    ],
                ]];
            }
        };

        self::expectException(SyntaxError::class);

        (new Factory($parser))->newInstance('test');
    }

    public function testInvalidExplodeOperator(): void
    {
        $parser = new class() implements Parser {
            public function parse(string $notation): array
            {
                return [[
                    'definition' => [
                        'simple' => ['type' => 'D6', 'quantity' => '4'],
                    ],
                    'modifiers' => [
                        ['modifier' => 'explode', 'operator' => '>=', 'value' => 2],
                    ],
                ]];
            }
        };

        self::expectException(SyntaxError::class);

        (new Factory($parser))->newInstance('test');
    }
}
