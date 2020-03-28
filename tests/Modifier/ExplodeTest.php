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
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Factory;
use Bakame\DiceRoller\Modifier\Explode;
use Bakame\DiceRoller\NotationParser;
use Bakame\DiceRoller\Tracer\Psr3Logger;
use Bakame\DiceRoller\Tracer\Psr3LogTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use function json_encode;

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
        $this->cup = Cup::fromRollable(new SidedDie(6), 4);
    }

    /**
     * @dataProvider provideInvalidProperties
     *
     * @covers ::__construct
     * @covers ::isValidPool
     * @covers ::isValidRollable
     * @covers \Bakame\DiceRoller\Exception\SyntaxError
     */
    public function testConstructorThrows(Pool $cup, string $compare, int $threshold): void
    {
        self::expectException(SyntaxError::class);
        new Explode($cup, $compare, $threshold);
    }

    public function provideInvalidProperties(): iterable
    {
        $cup = (new Factory(new NotationParser()))->newInstance('4d6');

        return [
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
     * @dataProvider provideInvalidProperties
     *
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\Exception\UnknownAlgorithm
     */
    public function testConstructorThrowsUnknownAlgorithmException(): void
    {
        self::expectException(UnknownAlgorithm::class);

        $cup = (new Factory(new NotationParser()))->newInstance('4d6');
        new Explode($cup, 'foobar', 6);
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

    public function provideExplodingModifier(): iterable
    {
        return [
            [
                'roll' => new Explode(new Cup(new SidedDie(3), new SidedDie(3), new SidedDie(4)), Explode::EQ, 3),
                'annotation' => '(2D3+D4)!=3',
            ],
            [
                'roll' => new Explode(Cup::fromRollable(new CustomDie(-1, -1, -1), 4), Explode::GT, 1),
                'annotation' => '4D[-1,-1,-1]!>1',
            ],
            [
                'roll' => new Explode(Cup::fromRollable(new SidedDie(6), 4), Explode::EQ, 1),
                'annotation' => '4D6!',
            ],
            [
                'roll' => new Explode(new SidedDie(6), Explode::EQ, 3),
                'annotation' => 'D6!=3',
            ],
        ];
    }

    /**
     * @covers ::__construct
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
        $explode = new Explode($this->cup, $algo, $threshold);
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
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::calculate
     * @covers ::setTracer
     * @covers ::getInnerRollable
     * @covers \Bakame\DiceRoller\Tracer\Psr3LogTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3Logger
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $explode = new Explode(new CustomDie(-1, -1, -2), Explode::EQ, -1);
        $explode->setTracer($tracer);
        $explode->roll();
        $explode->maximum();
        $explode->minimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
        self::assertInstanceOf(CustomDie::class, $explode->getInnerRollable());
    }
}
