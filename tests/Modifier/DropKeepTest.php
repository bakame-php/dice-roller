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
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Modifier\DropKeep;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\Tracer\Psr3Logger;
use Bakame\DiceRoller\Tracer\Psr3LogTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\Modifier\DropKeep
 */
final class DropKeepTest extends TestCase
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
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\Exception\SyntaxError
     */
    public function testConstructorThrows1(): void
    {
        self::expectException(SyntaxError::class);

        new DropKeep($this->cup, DropKeep::DROP_LOWEST, 6);
    }

    /**
     * @covers ::__construct
     * @covers \Bakame\DiceRoller\Exception\UnknownAlgorithm
     */
    public function testConstructorThrows2(): void
    {
        self::expectException(UnknownAlgorithm::class);

        new DropKeep($this->cup, 'foobar', 3);
    }

    /**
     * @covers ::notation
     * @covers ::jsonSerialize
     */
    public function testToString(): void
    {
        $cup = new DropKeep((new Cup())->withAddedRollable(
            new SidedDie(3),
            CustomDie::fromNotation('d[-3, -2, -1]'),
            new SidedDie(4)
        ), DropKeep::DROP_LOWEST, 2);

        $expectedNotation = '(D3+D[-3,-2,-1]+D4)DL2';
        self::assertSame($expectedNotation, $cup->notation());
        self::assertSame(json_encode($expectedNotation), json_encode($cup));
    }


    /**
     * @covers ::roll
     * @covers ::decorate
     */
    public function testGetTrace(): void
    {
        $dice1 = new class() implements Rollable {
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

        $dice2 = new class() implements Rollable {
            public function minimum(): int
            {
                return 2;
            }

            public function maximum(): int
            {
                return 2;
            }

            public function roll(): Roll
            {
                return new Toss(2, '2');
            }

            public function notation(): string
            {
                return '2';
            }

            public function jsonSerialize(): string
            {
                return $this->notation();
            }
        };

        $rollables = (new Cup())->withAddedRollable($dice1, clone $dice1, $dice2, clone $dice2);
        $cup = new DropKeep($rollables, DropKeep::DROP_LOWEST, 1);
        self::assertSame(5, $cup->roll()->value());
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::decorate
     * @covers ::filter
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::drop
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::roll
     * @dataProvider validParametersProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max): void
    {
        $cup = new DropKeep($this->cup, $algo, $threshold);
        $result = $cup->roll()->value();
        self::assertSame($min, $cup->minimum());
        self::assertSame($max, $cup->maximum());
        self::assertGreaterThanOrEqual($min, $result);
        self::assertLessThanOrEqual($max, $result);
    }

    public function validParametersProvider(): iterable
    {
        return [
            'dl' => [
                'algo' => DropKeep::DROP_LOWEST,
                'threshold' => 3,
                'min' => 1,
                'max' => 6,
            ],
            'dh' => [
                'algo' => DropKeep::DROP_HIGHEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kl' => [
                'algo' => DropKeep::KEEP_LOWEST,
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kh' => [
                'algo' => DropKeep::KEEP_HIGHEST,
                'threshold' => 3,
                'min' => 3,
                'max' => 18,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::filter
     * @covers ::setTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3LogTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3Logger
     * @covers ::getInnerRollable
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $dropKeep = new DropKeep(Cup::fromRollable(new SidedDie(6), 3), DropKeep::DROP_LOWEST, 2);
        $dropKeep->setTracer($tracer);
        $dropKeep->roll();
        $dropKeep->maximum();
        $dropKeep->minimum();
        self::assertInstanceOf(Pool::class, $dropKeep->getInnerRollable());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));

        $pool = CustomDie::fromNotation('d[-1, -2, -3]');
        $dropKeep = new DropKeep($pool, DropKeep::KEEP_LOWEST, 1);
        $dropKeep->roll();
        self::assertGreaterThan(3, $logger->getLogs(LogLevel::DEBUG));
    }

    /**
     * @covers ::getInnerRollable
     */
    public function testGetInnerRollableMethod(): void
    {
        $custom = CustomDie::fromNotation('d[1,2,3]');
        $rollable = new DropKeep($custom, DropKeep::DROP_LOWEST, 1);
        self::assertSame($custom, $rollable->getInnerRollable());
    }
}
