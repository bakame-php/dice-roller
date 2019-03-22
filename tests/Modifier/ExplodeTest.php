<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Modifier;

use Bakame\DiceRoller\Contract\Pool;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDie;
use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\ExpressionParser;
use Bakame\DiceRoller\Factory;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\Profiler\Logger;
use Bakame\DiceRoller\Profiler\LogProfiler;
use Bakame\DiceRoller\SidedDie;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Modifier\Explode
 */
final class ExplodeTest extends TestCase
{
    /**
     * @var Cup
     */
    private $cup;

    public function setUp(): void
    {
        $this->cup = Cup::createFromRollable(new SidedDie(6), 4);
    }

    /**
     * @dataProvider provideInvalidProperties
     *
     * @covers ::__construct
     * @covers ::isValidPool
     * @covers ::isValidRollable
     *
     */
    public function testConstructorThrows(Pool $cup, string $compare, int $threshold): void
    {
        self::expectException(CanNotBeRolled::class);
        new Explode($cup, $compare, $threshold);
    }

    public function provideInvalidProperties(): iterable
    {
        $cup = (new Factory(new ExpressionParser()))->newInstance('4d6');
        return [
            'invalid comparion' => [
                'cup' => $cup,
                'compare' => 'foobar',
                'threshold' => 6,
            ],
            'greater than invalid threshold' => [
                'cup' => $cup,
                'compare' => Explode::GT,
                'threshold' => 0,
            ],
            'lesser than invalid threshold' => [
                'cup' => $cup,
                'compare' => Explode::LT,
                'threshold' => 7,
            ],
            'equals invalid threshold' => [
                'cup' => new Cup(new CustomDie(1, 1, 1)),
                'compare' => Explode::EQ,
                'threshold' => 1,
            ],
            'empty cup object' => [
                'cup' => new Cup(),
                'compare' => Explode::EQ,
                'threshold' => 2,
            ],
        ];
    }

    /**
     * @dataProvider provideExplodingModifier
     *
     * @covers ::__construct
     * @covers ::toString
     * @covers ::getAnnotationSuffix
     *
     */
    public function testToString(Explode $roll, string $annotation): void
    {
        self::assertSame($annotation, $roll->toString());
    }

    public function provideExplodingModifier(): iterable
    {
        return [
            [
                'roll' => new Explode(new Cup(new SidedDie(3), new SidedDie(3), new SidedDie(4)), Explode::EQ, 3),
                'annotation' => '(2D3+D4)!=3',
            ],
            [
                'roll' => new Explode(Cup::createFromRollable(new CustomDie(-1, -1, -1), 4), Explode::GT, 1),
                'annotation' => '4D[-1,-1,-1]!>1',
            ],
            [
                'roll' => new Explode(Cup::createFromRollable(new SidedDie(6), 4), Explode::EQ, 1),
                'annotation' => '4D6!',
            ],
            [
                'roll' => new Explode(new SidedDie(6), Explode::EQ, 3),
                'annotation' => 'D6!=3',
            ],
        ];
    }

    /**
     * @covers ::getInnerRollable
     * @covers ::getTrace
     * @covers ::setTrace
     * @throws \Bakame\DiceRoller\Exception\IllegalValue
     * @throws \Bakame\DiceRoller\Exception\UnknownAlgorithm
     * @throws \ReflectionException
     */
    public function testGetTrace(): void
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will(self::onConsecutiveCalls(-1, -1, 3));

        $pool = new Cup($dice);
        $cup = new Explode($pool, Explode::EQ, -1);
        self::assertSame(1, $cup->roll());
        self::assertSame($pool, $cup->getInnerRollable());
        self::assertSame('(-1) + (-1) + 3', $cup->getTrace());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::isValid
     * @covers ::roll
     * @dataProvider validParametersProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max): void
    {
        $cup = new Explode($this->cup, $algo, $threshold);
        $res = $cup->roll();
        self::assertSame($min, $cup->getMinimum());
        self::assertSame($max, $cup->getMaximum());
        self::assertGreaterThanOrEqual($min, $res);
        self::assertLessThanOrEqual($max, $res);
    }

    public function validParametersProvider(): iterable
    {
        return [
            'equals' => [
                'algo' => Explode::EQ,
                'threshold' => 3,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'greater than' => [
                'algo' => Explode::GT,
                'threshold' => 5,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'lesser than' => [
                'algo' => Explode::LT,
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::calculate
     * @covers ::setProfiler
     * @covers ::setTrace
     * @covers ::getTrace
     * @covers \Bakame\DiceRoller\Profiler\LogProfiler
     * @covers \Bakame\DiceRoller\Profiler\Logger
     */
    public function testProfiler(): void
    {
        $logger = new Logger();
        $profiler = new LogProfiler($logger, LogLevel::DEBUG);
        $roll = new Explode(new CustomDie(-1, -1, -2), Explode::EQ, -1);
        $roll->setProfiler($profiler);
        self::assertSame('', $roll->getTrace());
        $roll->roll();
        self::assertNotEmpty($roll->getTrace());
        $roll->getMaximum();
        $roll->getMinimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
