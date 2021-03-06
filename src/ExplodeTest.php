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
 * @coversDefaultClass \Bakame\DiceRoller\Explode
 */
final class ExplodeTest extends TestCase
{
    private Cup $cup;

    public function setUp(): void
    {
        $this->cup = Cup::of(4, new SidedDie(6));
    }

    /**
     * @dataProvider provideInvalidProperties
     *
     * @covers ::equals
     * @covers ::greaterThan
     * @covers ::lesserThan
     * @covers ::__construct
     * @covers ::isValidPool
     * @covers ::isValidRollable
     * @covers \Bakame\DiceRoller\SyntaxError
     */
    public function testConstructorThrows(Pool $cup, string $compare, int $threshold): void
    {
        self::expectException(SyntaxError::class);

        if ('=' === $compare) {
            Explode::equals($cup, $threshold);
        } elseif ('>' === $compare) {
            Explode::greaterThan($cup, $threshold);
        } elseif ('<' === $compare) {
            Explode::lesserThan($cup, $threshold);
        }
    }

    public function provideInvalidProperties(): iterable
    {
        $cup = Cup::of(4, new SidedDie(6));

        return [
            'greater than invalid threshold' => [
                'cup' => $cup,
                'compare' => '>',
                'threshold' => 0,
            ],
            'lesser than invalid threshold' => [
                'cup' => $cup,
                'compare' => '<',
                'threshold' => 7,
            ],
            'equals invalid threshold' => [
                'cup' => new Cup(CustomDie::fromNotation('d[1, 1, 1]')),
                'compare' => '=',
                'threshold' => 1,
            ],
            'empty cup object' => [
                'cup' => new Cup(),
                'compare' => '=',
                'threshold' => 2,
            ],
        ];
    }

    public function testGetInnerRollable(): void
    {
        $rollable = new FudgeDie();

        self::assertSame($rollable, Explode::equals($rollable, 1)->getInnerRollable());
    }

    /**
     * @dataProvider provideExplodingModifier
     *
     * @covers ::__construct
     * @covers ::notation
     * @covers ::getAnnotationSuffix
     * @covers ::jsonSerialize
     */
    public function testToString(Explode $roll, string $notation): void
    {
        self::assertSame($notation, $roll->notation());
        self::assertSame(json_encode($notation), json_encode($roll));
    }

    /**
     * @covers ::isValid
     */
    public function testExplodeGreaterThen(): void
    {
        $rollable = Explode::greaterThan(Cup::of(4, CustomDie::fromNotation('d[-1, -1, -1]')), 1);
        $roll = $rollable->roll();

        self::assertTrue($roll->value() <= $rollable->maximum());
        self::assertTrue($roll->value() >= $rollable->minimum());
    }

    public function provideExplodingModifier(): iterable
    {
        return [
            [
                'roll' => Explode::equals(new Cup(new SidedDie(3), new SidedDie(3), new SidedDie(4)), 3),
                'annotation' => '(2D3+D4)!=3',
            ],
            [
                'roll' => Explode::greaterThan(Cup::of(4, CustomDie::fromNotation('d[-1, -1, -1]')), 1),
                'annotation' => '4D[-1,-1,-1]!>1',
            ],
            [
                'roll' => Explode::equals(Cup::of(4, new SidedDie(6)), 1),
                'annotation' => '4D6!',
            ],
            [
                'roll' => Explode::equals(new SidedDie(6), 3),
                'annotation' => 'D6!=3',
            ],
            [
                'roll' => Explode::lesserThan(new SidedDie(6), 3),
                'annotation' => 'D6!<3',
            ],
        ];
    }

    /**
     * @covers ::equals
     * @covers ::greaterThan
     * @covers ::lesserThan
     * @covers ::getInnerRollable
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::calculate
     * @covers ::isValid
     * @covers ::roll
     * @dataProvider validParametersProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max): void
    {
        if ('=' === $algo) {
            $explode = Explode::equals($this->cup, $threshold);
        } elseif ('>' === $algo) {
            $explode = Explode::greaterThan($this->cup, $threshold);
        } else {
            $explode = Explode::lesserThan($this->cup, $threshold);
        }

        $rollValue = $explode->roll()->value();
        self::assertSame($this->cup, $explode->getInnerRollable());
        self::assertSame($min, $explode->minimum());
        self::assertSame($max, $explode->maximum());
        self::assertGreaterThanOrEqual($min, $rollValue);
        self::assertLessThanOrEqual($max, $rollValue);
    }

    public function validParametersProvider(): iterable
    {
        return [
            'equals' => [
                'algo' => '=',
                'threshold' => 3,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'greater than' => [
                'algo' => '=',
                'threshold' => 5,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
            'lesser than' => [
                'algo' => '=',
                'threshold' => 2,
                'min' => 4,
                'max' => PHP_INT_MAX,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::calculate
     * @covers ::setTracer
     * @covers ::getTracer
     * @covers ::isValid
     * @covers ::getInnerRollable
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $explode = Explode::lesserThan(
            CustomDie::fromNotation('d[-1, -1, -2]'),
            -2,
            $tracer
        );
        $explode->setTracer($tracer);
        $explode->roll();
        $explode->maximum();
        $explode->minimum();
        self::assertSame($tracer, $explode->getTracer());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertInstanceOf(CustomDie::class, $explode->getInnerRollable());
    }
}
