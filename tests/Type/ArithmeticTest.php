<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Type;

use Bakame\DiceRoller\Exception\RollException;
use Bakame\DiceRoller\Profiler\Logger;
use Bakame\DiceRoller\Profiler\Profiler;
use Bakame\DiceRoller\Type\Arithmetic;
use Bakame\DiceRoller\Type\Cup;
use Bakame\DiceRoller\Type\CustomDice;
use Bakame\DiceRoller\Type\Dice;
use Bakame\DiceRoller\Type\Rollable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Type\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows1(): void
    {
        self::expectException(RollException::class);
        new Arithmetic(new Dice(6), '+', -3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows2(): void
    {
        self::expectException(RollException::class);
        new Arithmetic(new Dice(6), '**', 3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows3(): void
    {
        self::expectException(RollException::class);
        new Arithmetic(new Dice(6), '/', 0);
    }

    /**
     * @covers ::toString
     * @covers ::__toString
     */
    public function testToString(): void
    {
        $cup = new Arithmetic(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), '^', 3);
        self::assertSame('(2D3+D4)^3', (string) $cup);
    }

    /**
     * @covers ::roll
     */
    public function testGetTrace(): void
    {
        $dice = new class() implements Rollable {
            public function getMinimum(): int
            {
                return 1;
            }

            public function getMaximum(): int
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

        $rollables = new Cup($dice, clone $dice);
        $cup = new Arithmetic($rollables, '*', 3);
        self::assertSame(6, $cup->roll());
    }

    /**
     * @covers ::roll
     * @covers ::calculate
     */
    public function testRollWithNegativeDiceValue(): void
    {
        $dice = $this->createMock(Rollable::class);
        $dice->method('roll')
            ->will(self::returnValue(-1));

        $cup = new Arithmetic($dice, Arithmetic::EXPONENTIATION, 3);
        self::assertSame(-1, $dice->roll());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::roll
     * @covers ::calculate
     * @dataProvider validParametersProvider
     */
    public function testArithmetic(string $operator, int $size, int $value, int $min, int $max): void
    {
        $roll = new Arithmetic(new Dice($size), $operator, $value);
        $test = $roll->roll();
        self::assertSame($min, $roll->getMinimum());
        self::assertSame($max, $roll->getMaximum());
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
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
     * @covers ::roll
     * @covers ::calculate
     */
    public function testArithmeticExponentWithNegativeValue(): void
    {
        $roll = new Arithmetic(new CustomDice(-1, -1, -1), Arithmetic::EXPONENTIATION, 3);
        $test = $roll->roll();
        self::assertSame(-1, $roll->getMinimum());
        self::assertSame(-1, $roll->getMaximum());
        self::assertGreaterThanOrEqual(-1, $test);
        self::assertLessThanOrEqual(-1, $test);
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::calculate
     * @covers ::setTrace
     * @covers \Bakame\DiceRoller\Profiler\Profiler
     * @covers \Bakame\DiceRoller\Profiler\Logger
     */
    public function testProfiler(): void
    {
        $logger = new Logger();
        $roll = new Arithmetic(
            new CustomDice(-1, -1, -1),
            Arithmetic::EXPONENTIATION,
            3,
            new Profiler($logger, LogLevel::DEBUG)
        );
        $roll->roll();
        $roll->getMaximum();
        $roll->getMinimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertCount(1, $logger->getLogs());
        self::assertCount(1, $logger->getLogs(null));
        self::assertCount(0, $logger->getLogs('foobar'));
    }
}
