<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Decorator;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Decorator\Arithmetic;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\Tracer\Logger;
use Bakame\DiceRoller\Tracer\LogTracer;
use Bakame\DiceRoller\Tracer\NullTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Decorator\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows1(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new Dice(6), '+', -3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows2(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new Dice(6), '**', 3);
    }

    /**
     * @covers ::__construct
     */
    public function testArithmeticConstructorThrows3(): void
    {
        self::expectException(CanNotBeRolled::class);
        new Arithmetic(new Dice(6), '/', 0);
    }

    /**
     * @covers ::toString
     * @covers ::getInnerRollable
     * @covers ::getTracer
     */
    public function testToString(): void
    {
        $pool = (new Cup())->withAddedRollable(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        );

        $cup = new Arithmetic($pool, '^', 3);
        self::assertSame('(2D3+D4)^3', $cup->toString());
        self::assertSame($pool, $cup->getInnerRollable());
        self::assertInstanceOf(NullTracer::class, $cup->getTracer());
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

        $rollables = (new Cup())->withAddedRollable($dice, clone $dice);
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
     * @covers \Bakame\DiceRoller\Tracer\LogTracer
     * @covers \Bakame\DiceRoller\Tracer\Logger
     */
    public function testProfiler(): void
    {
        $logger = new Logger();
        $roll = new Arithmetic(
            new CustomDice(-1, -1, -1),
            Arithmetic::EXPONENTIATION,
            3,
            new LogTracer($logger, LogLevel::DEBUG)
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
