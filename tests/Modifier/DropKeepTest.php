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
     * @covers ::notation
     * @covers ::jsonSerialize
     */
    public function testToString(): void
    {
        $cup = DropKeep::dropLowest((new Cup())->withAddedRollable(
            new SidedDie(3),
            CustomDie::fromNotation('d[-3, -2, -1]'),
            new SidedDie(4)
        ), 2);

        $expectedNotation = '(D3+D[-3,-2,-1]+D4)DL2';
        self::assertSame($expectedNotation, $cup->notation());
        self::assertSame(json_encode($expectedNotation), json_encode($cup));
    }

    public function testThrowsExceptionOnConstructorError(): void
    {
        self::expectException(SyntaxError::class);

        DropKeep::dropHighest(Cup::fromRollable(new SidedDie(6), 23), 56);
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

        self::assertSame(5, DropKeep::dropLowest($rollables, 1)->roll()->value());
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::decorate
     * @covers ::dropLowest
     * @covers ::dropHighest
     * @covers ::slice
     * @covers ::keepLowest
     * @covers ::keepHighest
     * @covers ::roll
     * @dataProvider validParametersProvider
     */
    public function testModifier(string $algo, int $threshold, int $min, int $max): void
    {
        if ('DL' === $algo) {
            $cup = DropKeep::dropLowest($this->cup, $threshold);
        } elseif ('DH' === $algo) {
            $cup = DropKeep::dropHighest($this->cup, $threshold);
        } elseif ('KL' === $algo) {
            $cup = DropKeep::keepLowest($this->cup, $threshold);
        } elseif ('KH' === $algo) {
            $cup = DropKeep::keepHighest($this->cup, $threshold);
        }

        self::assertSame($min, $cup->minimum());
        self::assertSame($max, $cup->maximum());

        $result = $cup->roll()->value();
        self::assertGreaterThanOrEqual($min, $result);
        self::assertLessThanOrEqual($max, $result);
    }

    public function validParametersProvider(): iterable
    {
        return [
            'dl' => [
                'algo' => 'DL',
                'threshold' => 3,
                'min' => 1,
                'max' => 6,
            ],
            'dh' => [
                'algo' => 'DH',
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kl' => [
                'algo' => 'KL',
                'threshold' => 2,
                'min' => 2,
                'max' => 12,
            ],
            'kh' => [
                'algo' => 'KH',
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
     * @covers ::setTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3LogTracer
     * @covers \Bakame\DiceRoller\Tracer\Psr3Logger
     * @covers ::getInnerRollable
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $dropKeep = DropKeep::dropLowest(
            Cup::fromRollable(new SidedDie(6), 3),
            2,
            new Psr3LogTracer($logger, LogLevel::DEBUG)
        );
        $dropKeep->roll();
        $dropKeep->maximum();
        $dropKeep->minimum();

        self::assertInstanceOf(Pool::class, $dropKeep->getInnerRollable());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));

        $pool = CustomDie::fromNotation('d[-1, -2, -3]');
        $dropKeep = DropKeep::dropLowest($pool, 1);
        $dropKeep->roll();
        self::assertGreaterThan(3, $logger->getLogs(LogLevel::DEBUG));
    }

    /**
     * @covers ::getInnerRollable
     */
    public function testGetInnerRollableMethod(): void
    {
        $custom = CustomDie::fromNotation('d[1,2,3]');
        $rollable = DropKeep::dropLowest($custom, 1);

        self::assertSame($custom, $rollable->getInnerRollable());
    }
}
