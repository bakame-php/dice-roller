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
        $this->factory = Factory::fromSystem();
    }

    /**
     * @covers ::__construct
     * @covers ::newInstance
     * @covers \Bakame\DiceRoller\SyntaxError
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
     * @covers \Bakame\DiceRoller\Arithmetic::fromOperation
     * @covers \Bakame\DiceRoller\DropKeep::fromAlgorithm
     * @covers \Bakame\DiceRoller\Explode::fromAlgorithm
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

        $factory = new Factory($randomIntProvider);

        $dice = $factory->newInstance('d8');
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
     * @covers \Bakame\DiceRoller\SidedDie::size
     */
    public function testRollWithDefaultDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 5;
            }
        };

        $factory = new Factory($randomIntProvider);
        $dice = $factory->newInstance('d');
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
     * @covers \Bakame\DiceRoller\SidedDie::size
     */
    public function testRollWithMultipleDice(): void
    {
        $randomIntProvider = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 2;
            }
        };

        $factory = new Factory($randomIntProvider);
        $cup = $factory->newInstance('2D6+3d4');
        self::assertInstanceOf(Traversable::class, $cup);
        self::assertCount(2, $cup);
        $res = iterator_to_array($cup, false);
        self::assertInstanceOf(Cup::class, $res[0]);
        self::assertCount(2, $res[0]);
        foreach ($res[0] as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(6, $dice->size());
        }

        self::assertInstanceOf(Cup::class, $res[1]);
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

    public function testInvalidDropKeepOperator(): void
    {
        self::expectException(SyntaxError::class);

        Factory::fromSystem()->newInstance('3D4%2');
    }

    public function testInvalidExplodeOperator(): void
    {
        self::expectException(SyntaxError::class);

        Factory::fromSystem()->newInstance('test');
    }

    public function testNewInstanceAddRecursivelyTheTracer(): void
    {
        $tracer = new MemoryTracer();
        $pool = $this->factory->newInstance('2D6+D%+3', $tracer);
        if ($pool instanceof SupportsTracing) {
            self::assertSame($tracer, $pool->getTracer());
        }

        if ($pool instanceof EnablesDeepTracing && $pool instanceof Pool) {
            foreach ($pool as $item) {
                if ($item instanceof SupportsTracing) {
                    self::assertSame($tracer, $item->getTracer());
                }
            }
        }
    }

    public function testNewInstanceAddTheTracer(): void
    {
        $tracer = new MemoryTracer();
        $pool = $this->factory->newInstance('D6', $tracer);

        if ($pool instanceof SupportsTracing) {
            self::assertSame($tracer, $pool->getTracer());
        }
    }


    /**
     * @dataProvider invalidStringParsingProvider
     */
    public function testInvalidParserDefinition(string $expected): void
    {
        self::expectException(SyntaxError::class);

        $this->factory->newInstance($expected);
    }

    public function invalidStringParsingProvider(): iterable
    {
        return [
            'missing separator D' => ['ZZZ'],
            'missing group definition' => ['+'],
            'invalid group' => ['10+3dF'],
            'invalid modifier' => ['3dFZZZZ'],
            'invalid complex cup' => ['(3DF+2D6)*3+3F^2'],
            'invalid complex cup 2' => ['(3DFoobar+2D6)*3+3DF^2'],
            'invalid complex cup 3' => ['()*3'],
            'invalid custom dice' => ['3dss'],
        ];
    }

    /**
     * @dataProvider permissiveParserProvider
     */
    public function testPermissiveParser(string $full, string $short): void
    {
        self::assertEquals($this->factory->newInstance($short)->notation(), $this->factory->newInstance($full)->notation());
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
     * @dataProvider validStringParserProvider
     */
    public function testInnerParser(string $notation, string $parsed): void
    {
        self::assertSame($parsed, $this->factory->newInstance($notation)->notation());
    }

    public function validStringParserProvider(): iterable
    {
        return [
            'empty cup' => [
                'notation' => '',
                'parsed' => '0',
            ],
            'simple' => [
                'notation' => '2D3',
                'parsed' => '2D3',
            ],
            'empty nb dice' => [
                'notation' => 'd3',
                'parsed' => 'D3',
            ],
            'empty nb sides' => [
                'notation' => '3d',
                'parsed' => '3D6',
            ],
            'mixed group' => [
                'notation' => '2D3+1D4',
                'parsed' => '2D3+D4',
            ],
            'case insensitive' => [
                'notation' => '2d3+1d4',
                'parsed' => '2D3+D4',
            ],
            'default to one dice' => [
                'notation' => 'd3+d4+1d3+5d2',
                'parsed' => '2D3+D4+5D2',
            ],
            'fudge dice' => [
                'notation' => '2dF',
                'parsed' => '2DF',
            ],
            'multiple fudge dice' => [
                'notation' => 'dF+3dF',
                'parsed' => 'DF+3DF',
            ],
            'mixed cup' => [
                'notation' => '2df+3d2',
                'parsed' => '2DF+3D2',
            ],
            'add modifier' => [
                'notation' => '2d3-4',
                'parsed' => '2D3-4',
            ],
            'add modifier to multiple group' => [
                'notation' => '2d3+4+3dF!>1/4^3',
                'parsed' => '2D3+4+3DF!>1/4^3',
            ],
            'add explode modifier' => [
                'notation' => '2d3!',
                'parsed' => '2D3!',
            ],
            'add keep lowest modifier' => [
                'notation' => '2d3kl1',
                'parsed' => '2D3KL1',
            ],
            'add keep highest modifier' => [
                'notation' => '2d3kh2',
                'parsed' => '2D3KH2',
            ],
            'add drop lowest modifier' => [
                'notation' => '4d6dl2',
                'parsed' => '4D6DL2',
            ],
            'add drop highest modifier' => [
                'notation' => '4d6dh3',
                'parsed' =>'4D6DH3',
            ],
            'complex mixed cup' => [
                'notation' => '(3DF+2D6)*3+3DF^2',
                'parsed' => '(3DF+2D6)*3+3DF^2',
            ],
            'percentile dice' => [
                'notation' => '3d%',
                'parsed' => '3D%',
            ],
            'custom dice' => [
                'notation' => '2d[1,2,34]',
                'parsed' => '2D[1,2,34]',
            ],
        ];
    }
}
