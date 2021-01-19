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
 * @coversDefaultClass \Bakame\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    private Tracer $tracer;

    public function setUp(): void
    {
        $this->tracer = Psr3LogTracer::fromNullLogger();
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollable(): void
    {
        $cup = new Cup();
        $altCup = $cup->withAddedRollable(new FudgeDie(), CustomDie::fromNotation('D[-1,1,-1]'));
        self::assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollableReturnsSameInstance(): void
    {
        $cup = new Cup(new FudgeDie());
        $altCup = $cup->withAddedRollable(new Cup());

        self::assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::notation
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::count
     * @covers ::getIterator
     * @covers ::isEmpty
     */
    public function testRoll(): void
    {
        $factory = Factory::fromSystem();
        $cup = new Cup($factory->newInstance('4D10'), $factory->newInstance('2d4'));
        self::assertFalse($cup->isEmpty());
        self::assertSame(6, $cup->minimum());
        self::assertSame(48, $cup->maximum());
        self::assertSame('4D10+2D4', $cup->notation());
        self::assertCount(2, $cup);
        self::assertContainsOnlyInstancesOf(Rollable::class, $cup);
        for ($i = 0; $i < 5; $i++) {
            $result = $cup->roll()->value();
            self::assertGreaterThanOrEqual($cup->minimum(), $result);
            self::assertLessThanOrEqual($cup->maximum(), $result);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::of
     * @dataProvider validNamedConstructor
     */
    public function testCreateFromRollable(int $quantity, Rollable $template): void
    {
        $cup = Cup::of($quantity, $template);
        self::assertCount($quantity, $cup);
        self::assertContainsOnlyInstancesOf(get_class($template), $cup);
    }

    public function validNamedConstructor(): iterable
    {
        return [
            'basic dice' => [
                'quantity' => 2,
                'template' => new SidedDie(6),
            ],
            'fudge dice' => [
                'quantity' => 3,
                'template' => new FudgeDie(),
            ],
            'percentile dice' => [
                'quantity' => 4,
                'template' => new PercentileDie(),

            ],
            'custom dice' => [
                'quantity' => 5,
                'template' => CustomDie::fromNotation('D[1, 2, 2, 3, 5]'),
            ],
        ];
    }

    public function testCreateFromRollableThrowsException(): void
    {
        self::expectException(SyntaxError::class);
        Cup::of(0, new FudgeDie());
    }

    /**
     * @covers ::__construct
     * @covers ::of
     * @covers ::withAddedRollable
     * @covers ::isValid
     */
    public function testCreateFromRollableReturnsEmptyCollection(): void
    {
        $cup = Cup::of(12, new Cup());
        $alt_cup = $cup->withAddedRollable(new Cup());
        self::assertCount(0, $cup);
        self::assertSame($cup, $alt_cup);
    }

    /**
     * @covers ::__construct
     * @covers ::notation
     * @covers ::isEmpty
     */
    public function testEmptyCup(): void
    {
        $cup = new Cup();
        self::assertSame('0', $cup->notation());
        self::assertTrue($cup->isEmpty());
        self::assertSame(0, $cup->roll()->value());
    }

    /**
     * @covers ::__construct
     * @covers ::minimum
     * @covers ::maximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::setTracer
     * @covers ::getTracer
     */
    public function testTracer(): void
    {
        $logger = new Psr3Logger();
        $tracer = new Psr3LogTracer($logger, LogLevel::DEBUG);
        $cup = Cup::of(12, CustomDie::fromNotation('d[2, -3, -5]'));
        self::assertEquals(new NullTracer(), $cup->getTracer());
        $cup->setTracer($tracer);
        $cup->roll();
        $cup->maximum();
        $cup->minimum();
        self::assertEquals($tracer, $cup->getTracer());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }

    /**
     * @covers ::count
     * @covers ::getIterator
     * @covers ::jsonSerialize
     * @covers \Bakame\DiceRoller\Toss
     * @covers \Bakame\DiceRoller\NullTracer
     */
    public function testFiveFourSidedDice(): void
    {
        $cup = Cup::of(5, new SidedDie(4));
        self::assertSame(json_encode('5D4'), json_encode($cup));
        self::assertCount(5, $cup);
        self::assertContainsOnlyInstancesOf(SidedDie::class, $cup);
        foreach ($cup as $dice) {
            self::assertInstanceOf(SidedDie::class, $dice);
            self::assertSame(4, $dice->size());
        }

        for ($i = 0; $i < 5; $i++) {
            $result = $cup->roll()->value();
            self::assertGreaterThanOrEqual($cup->minimum(), $result);
            self::assertLessThanOrEqual($cup->maximum(), $result);
        }
    }

    public function testResurciveTracerSetter(): void
    {
        $tracer = Psr3LogTracer::fromNullLogger();

        $die1 = SidedDie::fromNotation('D5');
        $die1->setTracer(new MemoryTracer());

        $die2 = Cup::of(3, new FudgeDie());
        $die2->setTracer(new NullTracer());

        $cup = new Cup($die1, $die2);
        $cup->setTracer(Psr3LogTracer::fromNullLogger());

        self::assertNotEquals($cup->getTracer(), $die1->getTracer());
        self::assertNotEquals($cup->getTracer(), $die2->getTracer());
        self::assertNotSame($tracer, $cup->getTracer());

        $cup->setTracerRecursively($tracer);

        self::assertSame($cup->getTracer(), $die1->getTracer());
        self::assertSame($cup->getTracer(), $die2->getTracer());
        self::assertSame($tracer, $cup->getTracer());
    }
}
