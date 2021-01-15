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
use Psr\Log\LogLevel;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Arithmetic
 */
final class ArithmeticTest extends TestCase
{
    /**
     * @covers ::add
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticAddThrows(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::add(new SidedDie(6), -3);
    }

    /**
     * @covers ::sub
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticSubThrows(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::sub(new SidedDie(6), -3);
    }

    /**
     * @covers ::mul
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticMulThrows(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::mul(new SidedDie(6), -3);
    }

    /**
     * @covers ::div
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticDivThrows(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::div(new SidedDie(6), 0);
    }

    /**
     * @covers ::pow
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticExpThrows(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::pow(new SidedDie(6), -3);
    }

    /**
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testArithmeticConstructorThrows3(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::div(new SidedDie(6), 0);
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

        $cup = Arithmetic::pow($pool, 3);

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
        $arithmetic = Arithmetic::mul($cup, 3);

        self::assertSame(6, $arithmetic->roll()->value());
    }

    /**
     * @covers ::roll
     * @covers ::decorate
     * @covers ::calculate
     */
    public function testRollWithNegativeDiceValue(): void
    {
        $cup = Arithmetic::pow(CustomDie::fromNotation('d[-1, -1, -1]'), 3);

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
     * @covers ::add
     * @covers ::sub
     * @covers ::mul
     * @covers ::div
     * @covers ::pow
     * @dataProvider validParametersProvider
     */
    public function testArithmetic(string $operator, int $size, int $value, int $min, int $max): void
    {
        if ('+' === $operator) {
            $roll = Arithmetic::add(new SidedDie($size), $value);
        } elseif ('-' === $operator) {
            $roll = Arithmetic::sub(new SidedDie($size), $value);
        } elseif ('*' === $operator) {
            $roll = Arithmetic::mul(new SidedDie($size), $value);
        } elseif ('/' === $operator) {
            $roll = Arithmetic::div(new SidedDie($size), $value);
        } else {
            $roll = Arithmetic::pow(new SidedDie($size), $value);
        }

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
        $randomIntGenerator = new class() implements RandomIntGenerator {
            public function generateInt(int $minimum, int $maximum): int
            {
                return 2;
            }
        };

        $arithmetic = Arithmetic::pow(CustomDie::fromNotation('d[-1, 10, 3]', $randomIntGenerator), 3);

        self::assertSame(-1, $arithmetic->minimum());
        self::assertSame(1000, $arithmetic->maximum());
        self::assertSame(27, $arithmetic->roll()->value());
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::calculate
     * @covers ::setTracer
     * @covers ::getTracer
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $arithmetic = Arithmetic::pow(CustomDie::fromNotation('d[-1, -1, -1]'), 3, $tracer);
        self::assertSame($tracer, $arithmetic->getTracer());

        $arithmetic->roll();
        $arithmetic->maximum();
        $arithmetic->minimum();

        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertCount(1, $logger->getLogs());
        self::assertCount(1, $logger->getLogs(null));
        self::assertCount(0, $logger->getLogs('foobar'));
    }

    public function testCreateFromOperation(): void
    {
        self::expectException(SyntaxError::class);

        Arithmetic::fromOperation(Cup::of(new SidedDie(6), 4), '/', 0);
    }
}
