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
use Bakame\DiceRoller\CustomDice;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\DiceRoller;
use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\Logger;
use Bakame\DiceRoller\PercentileDice;
use Bakame\DiceRoller\Profiler;
use Bakame\DiceRoller\Rollable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::withRollable
     */
    public function testWithRollable(): void
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withRollable(new CustomDice(-1, 1, -1));
        self::assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withRollable
     */
    public function testWithRollableReturnsSameInstance(): void
    {
        $cup = new Cup(new FudgeDice());
        $altCup = $cup->withRollable(new Cup());
        self::assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::__toString
     * @covers ::toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::count
     * @covers ::getIterator
     */
    public function testRoll(): void
    {
        $cup = new Cup(DiceRoller::parse('4D10'), DiceRoller::parse('2d4'));
        self::assertSame(6, $cup->getMinimum());
        self::assertSame(48, $cup->getMaximum());
        self::assertSame('4D10+2D4', (string) $cup);
        self::assertCount(2, $cup);
        self::assertContainsOnlyInstancesOf(Rollable::class, $cup);
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            self::assertGreaterThanOrEqual($cup->getMinimum(), $test);
            self::assertLessThanOrEqual($cup->getMaximum(), $test);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @dataProvider validNamedConstructor
     */
    public function testCreateFromRollable(int $quantity, Rollable $template): void
    {
        $cup = Cup::createFromRollable($quantity, $template);
        self::assertCount($quantity, $cup);
        self::assertContainsOnlyInstancesOf(get_class($template), $cup);
    }

    public function validNamedConstructor(): iterable
    {
        return [
            'basic dice' => [
                'quantity' => 2,
                'template' => new Dice(6),
            ],
            'fudge dice' => [
                'quantity' => 3,
                'template' => new FudgeDice(),
            ],
            'percentile dice' => [
                'quantity' => 4,
                'template' => new PercentileDice(),

            ],
            'custom dice' => [
                'quantity' => 5,
                'template' => new CustomDice(1, 2, 2, 3, 5),
            ],
        ];
    }

    public function testCreateFromRollableThrowsException(): void
    {
        self::expectException(Exception::class);
        Cup::createFromRollable(0, new FudgeDice());
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @covers ::withRollable
     * @covers ::isValid
     */
    public function testCreateFromRollableReturnsEmptyCollection(): void
    {
        $cup = Cup::createFromRollable(12, new Cup());
        $alt_cup = $cup->withRollable(new Cup());
        self::assertCount(0, $cup);
        self::assertSame($cup, $alt_cup);
    }

    /**
     * @covers ::__construct
     * @covers ::__toString
     * @covers ::toString
     */
    public function testEmptyCup(): void
    {
        $cup = new Cup();
        self::assertSame('0', (string) $cup);
    }


    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::setTrace
     */
    public function testProfiler(): void
    {
        $logger = new Logger();
        $profiler = new Profiler($logger, LogLevel::DEBUG);
        $cup = Cup::createFromRollable(12, new Dice(2), $profiler);
        $cup->roll();
        $cup->getMaximum();
        $cup->getMinimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
