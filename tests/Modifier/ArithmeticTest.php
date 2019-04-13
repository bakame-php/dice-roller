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

use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDie;
use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\LogProfiler;
use Bakame\DiceRoller\MemoryLogger;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\SidedDie;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Modifier\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows1(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new SidedDie(6), '+', -3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows2(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new SidedDie(6), '**', 3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows3(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new SidedDie(6), '/', 0);
    }

    /**
     * @covers ::toString
     * @covers ::getInnerRollable
     */
    public function testToString(): void
    {
        $pool = (new Cup())->withAddedRollable(
            new SidedDie(3),
            new SidedDie(3),
            new SidedDie(4)
        );

        $cup = new Arithmetic($pool, '^', 3);
        self::assertSame('(2D3+D4)^3', $cup->toString());
        self::assertSame($pool, $cup->getInnerRollable());
    }

    /**
     * @covers ::roll
     */
    public function testGetTrace(): void
    {
        $dice = new class() implements Rollable {
            public function minimum(): int
            {
                return 1;
            }

            public function maximum(): int
            {
                return 1;
            }

            public function roll(): int
            {
                return 1;
            }

            public function toString(): string
            {
                return '1';
            }
        };

        $rollables = (new Cup())->withAddedRollable($dice, clone $dice);
        $cup = new Arithmetic($rollables, '*', 3);
        self::assertSame(6, $cup->roll());
    }

    /**
     * @covers ::roll
     * @covers ::decorate
     * @covers ::calculate
     */
    public function testRollWithNegativeDiceValue(): void
    {
        $dice = new CustomDie(-1, -1, -1);

        $cup = new Arithmetic($dice, Arithmetic::EXP, 3);
        self::assertSame(-1, $cup->roll());
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::decorate
     * @covers ::calculate
     * @covers ::roll
     * @covers ::decorate
     * @dataProvider validParametersProvider
     */
    public function testArithmetic(string $operator, int $size, int $value, int $min, int $max): void
    {
        $roll = new Arithmetic(new SidedDie($size), $operator, $value);
        $test = $roll->roll();
        self::assertSame($min, $roll->minimum());
        self::assertSame($max, $roll->maximum());
        self::assertGreaterThanOrEqual($min, $test);
        self::assertLessThanOrEqual($max, $test);
    }

    public function validParametersProvider(): iterable
    {
        return [
            'adding' => [
                'operator' => '+',
                'size' => 6,
                'value' => 10,
                'min' => 11,
                'max' => 16,
            ],
            'substracting' => [
                'operator' => '-',
                'size' => 6,
                'value' => 3,
                'min' => -2,
                'max' => 3,
            ],
            'multiplying' => [
                'operator' => '*',
                'size' => 6,
                'value' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'intdiv' => [
                'operator' => '/',
                'size' => 6,
                'value' => 2,
                'min' => 0,
                'max' => 3,
            ],
            'pow' => [
                'operator' => '^',
                'size' => 6,
                'value' => 2,
                'min' => 1,
                'max' => 36,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::decorate
     * @covers ::calculate
     * @covers ::roll
     * @covers ::decorate
     */
    public function testArithmeticExponentWithNegativeValue(): void
    {
        $roll = new Arithmetic(new CustomDie(-1, -1, -1), Arithmetic::EXP, 3);
        $test = $roll->roll();
        self::assertSame(-1, $roll->minimum());
        self::assertSame(-1, $roll->maximum());
        self::assertGreaterThanOrEqual(-1, $test);
        self::assertLessThanOrEqual(-1, $test);
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::calculate
     * @covers ::getTrace
     * @covers ::getProfiler
     * @covers ::setProfiler
     * @covers \Bakame\DiceRoller\LogProfiler
     * @covers \Bakame\DiceRoller\MemoryLogger
     */
    public function testProfiler(): void
    {
        $logger = new MemoryLogger();
        $roll = new Arithmetic(
            new CustomDie(-1, -1, -1),
            Arithmetic::EXP,
            3
        );
        $profiler = new LogProfiler($logger, LogLevel::DEBUG);
        $roll->setProfiler($profiler);
        self::assertEmpty($roll->getTrace());
        $roll->roll();
        self::assertNotEmpty($roll->getTrace());
        $roll->maximum();
        $roll->minimum();
        self::assertSame($profiler, $roll->getProfiler());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertCount(1, $logger->getLogs());
        self::assertCount(1, $logger->getLogs(null));
        self::assertCount(0, $logger->getLogs('foobar'));
    }
}
