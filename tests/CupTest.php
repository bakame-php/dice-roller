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
use Bakame\DiceRoller\Exception\CanNotBeRolled;
use Bakame\DiceRoller\Factory;
use Bakame\DiceRoller\FudgeDice;
use Bakame\DiceRoller\PercentileDice;
use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\Tracer\Logger;
use Bakame\DiceRoller\Tracer\LogTracer;
use Bakame\DiceRoller\Tracer\NullTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    /**
     * @var \Bakame\DiceRoller\Tracer
     */
    private $tracer;

    public function setUp(): void
    {
        $this->tracer = new NullTracer();
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollable(): void
    {
        $cup = new Cup($this->tracer);
        $altCup = $cup->withAddedRollable(new FudgeDice(), new CustomDice(-1, 1, -1));
        self::assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollableReturnsSameInstance(): void
    {
        $cup = (new Cup($this->tracer))->withAddedRollable(new FudgeDice());
        $altCup = $cup->withAddedRollable(new Cup($this->tracer));

        self::assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::count
     * @covers ::getIterator
     * @covers ::isEmpty
     * @covers ::getTracer
     */
    public function testRoll(): void
    {
        $factory = new Factory();
        $cup = new Cup($this->tracer);
        $cup = $cup->withAddedRollable($factory->newInstance('4D10'), $factory->newInstance('2d4'));
        self::assertFalse($cup->isEmpty());
        self::assertSame(6, $cup->getMinimum());
        self::assertSame(48, $cup->getMaximum());
        self::assertSame('4D10+2D4', $cup->toString());
        self::assertCount(2, $cup);
        self::assertContainsOnlyInstancesOf(Rollable::class, $cup);
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            self::assertGreaterThanOrEqual($cup->getMinimum(), $test);
            self::assertLessThanOrEqual($cup->getMaximum(), $test);
        }
        self::assertSame($this->tracer, $cup->getTracer());
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @dataProvider validNamedConstructor
     */
    public function testCreateFromRollable(int $quantity, \Bakame\DiceRoller\Rollable $template): void
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
        self::expectException(CanNotBeRolled::class);
        Cup::createFromRollable(0, new FudgeDice());
    }

    /**
     * @covers ::__construct
     * @covers ::createFromRollable
     * @covers ::withAddedRollable
     * @covers ::isValid
     */
    public function testCreateFromRollableReturnsEmptyCollection(): void
    {
        $cup = Cup::createFromRollable(12, new Cup());
        $alt_cup = $cup->withAddedRollable(new Cup());
        self::assertCount(0, $cup);
        self::assertSame($cup, $alt_cup);
    }

    /**
     * @covers ::__construct
     * @covers ::toString
     * @covers ::isEmpty
     * @covers \Bakame\DiceRoller\Tracer\NullTracer
     */
    public function testEmptyCup(): void
    {
        $cup = new Cup();
        self::assertSame('0', $cup->toString());
        self::assertTrue($cup->isEmpty());
        self::assertSame(0, $cup->roll());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::setTrace
     */
    public function testTracer(): void
    {
        $logger = new Logger();
        $tracer = new LogTracer($logger, LogLevel::DEBUG);
        $cup = Cup::createFromRollable(12, new CustomDice(2, -3, -5), $tracer);
        $cup->roll();
        $cup->getMaximum();
        $cup->getMinimum();
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }
}
