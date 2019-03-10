<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\DropKeep;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Logger;
use Bakame\DiceRoller\Profiler;
use Bakame\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\DropKeep
 */
final class DropKeepTest extends TestCase
{
    /**
     * @var Cup
     */
    private $cup;

    public function setUp(): void
    {
        $this->cup = Cup::createFromRollable(4, new Dice(6));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows1(): void
    {
        self::expectException(Exception::class);
        new DropKeep($this->cup, DropKeep::DROP_LOWEST, 6);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorThrows2(): void
    {
        self::expectException(Exception::class);
        new DropKeep($this->cup, 'foobar', 3);
    }

    /**
     * @covers ::toString
     * @covers ::__toString
     */
    public function testToString(): void
    {
        $cup = new DropKeep(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), DropKeep::DROP_LOWEST, 2);
        self::assertSame('(2D3+D4)DL2', (string) $cup);
    }


    /**
     * @covers ::roll
     */
    public function testGetTrace(): void
    {
        $dice1 = new class() implements Rollable {
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

        $dice2 = new class() implements Rollable {
            public function getMinimum(): int
            {
                return 2;
            }

            public function getMaximum(): int
            {
                return 2;
            }

            public function roll(): int
            {
                return 2;
            }

            public function toString(): string
            {
                return '2';
            }
        };

        $rollables = new Cup($dice1, clone $dice1, $dice2, clone $dice2);
        $cup = new DropKeep($rollables, DropKeep::DROP_LOWEST, 1);
        self::assertSame(5, $cup->roll());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::calculate
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
        $res = $cup->roll();
        self::assertSame($min, $cup->getMinimum());
        self::assertSame($max, $cup->getMaximum());
        self::assertGreaterThanOrEqual($min, $res);
        self::assertLessThanOrEqual($max, $res);
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
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::calculate
     * @covers ::setTrace
     * @covers \Bakame\DiceRoller\Profiler
     * @covers \Bakame\DiceRoller\Logger
     */
    public function testProfiler(): void
    {
        $logger = new Logger();
        $profiler = new Profiler($logger, LogLevel::DEBUG);
        $roll = new DropKeep(new Cup(
            new Dice(3),
            new Dice(3),
            new Dice(4)
        ), DropKeep::DROP_LOWEST, 2, $profiler);
        $roll->roll();
        $roll->getMaximum();
        $roll->getMinimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
