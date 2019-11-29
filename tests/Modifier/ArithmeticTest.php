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

use Bakame\DiceRoller\Contract\CanNotBeRolled;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Modifier\Arithmetic;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\Tracer\Psr3Logger;
use Bakame\DiceRoller\Tracer\Psr3LogTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use function json_encode;

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
     * @covers ::notation
     * @covers ::jsonSerialize
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
        self::assertSame('(2D3+D4)^3', $cup->notation());
        self::assertSame(json_encode('(2D3+D4)^3'), json_encode($cup));
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

            public function roll(): Roll
            {
                return new Toss(1, '1');
            }

            public function notation(): string
            {
                return '1';
            }

            public function jsonSerialize(): string
            {
                return $this->notation();
            }
        };

        $cup = (new Cup())->withAddedRollable($dice, clone $dice);
        $arithmetic = new Arithmetic($cup, '*', 3);
        self::assertSame(6, $arithmetic->roll()->value());
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
        self::assertSame(-1, $cup->roll()->value());
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
        $test = $roll->roll()->value();
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
        $arithmetic = new Arithmetic(new CustomDie(-1, -1, -1), Arithmetic::EXP, 3);
        $rollValue = $arithmetic->roll()->value();
        self::assertSame(-1, $arithmetic->minimum());
        self::assertSame(-1, $arithmetic->maximum());
        self::assertGreaterThanOrEqual(-1, $rollValue);
        self::assertLessThanOrEqual(-1, $rollValue);
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::calculate
     * @covers ::setTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3LogTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3Logger
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $arithmetic = new Arithmetic(
            new CustomDie(-1, -1, -1),
            Arithmetic::EXP,
            3
        );
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $arithmetic->setTracer($tracer);
        $arithmetic->roll();
        $arithmetic->maximum();
        $arithmetic->minimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertCount(1, $logger->getLogs());
        self::assertCount(1, $logger->getLogs(null));
        self::assertCount(0, $logger->getLogs('foobar'));
    }
}
