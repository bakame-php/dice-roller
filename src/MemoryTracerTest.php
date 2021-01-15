<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use function get_class;
use function iterator_to_array;
use function json_encode;

/**
 * @coversDefaultClass \Bakame\DiceRoller\MemoryTracer
 */
class MemoryTracerTest extends TestCase
{
    /**
     * @covers ::append
     * @covers ::count
     * @covers ::isEmpty
     * @covers ::reset
     */
    public function testAddTrace(): void
    {
        $tracer = new MemoryTracer();
        self::assertCount(0, $tracer);
        self::assertTrue($tracer->isEmpty());
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $cup->roll();
        self::assertCount(1, $tracer);
        self::assertFalse($tracer->isEmpty());
        $tracer->reset();
        self::assertCount(0, $tracer);
        self::assertTrue($tracer->isEmpty());
    }

    /**
     * @covers ::getIterator
     */
    public function testTracerIteration(): void
    {
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $roll = $cup->roll();
        $rolls = iterator_to_array($tracer);
        $lastTrace =  array_pop($rolls);

        self::assertEquals($roll, $lastTrace);
    }

    public function testJsonRepresentation(): void
    {
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);

        /** @var string $expectedJson */
        $expectedJson = json_encode([
            [
                'source' => get_class($cup).'::roll',
                'notation' => $cup->notation(),
            ] +  $cup->roll()->info(),
        ]);

        /** @var string $tracerJson */
        $tracerJson = json_encode($tracer);
        self::assertJsonStringEqualsJsonString($expectedJson, $tracerJson);
    }

    public function testOffsetAccessValid(): void
    {
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $minRoll = $cup->minimum();
        $regularRoll = $cup->roll();
        $maxRoll = $cup->maximum();
        $lastRoll = $cup->roll();
        self::assertCount(4, $tracer);
        self::assertSame($regularRoll, $tracer->get(1));
        self::assertSame($lastRoll, $tracer->get(-1));
    }

    public function testAccessingByOffsetThrowsOutOfBoundExceptionIfTracerIsEmpty(): void
    {
        self::expectException(OutOfBoundsException::class);
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $tracer->get(3);
    }

    public function testAccessingByOffsetThrowsOutOfBoundExceptionIfOffsetIsTooHightInTracer(): void
    {
        self::expectException(OutOfBoundsException::class);
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $cup->minimum();
        $tracer->get(3);
    }

    public function testAccessingByOffsetThrowsOutOfBoundExceptionIfNegativeOffsetIsTooLowInTracer(): void
    {
        self::expectException(OutOfBoundsException::class);
        $tracer = new MemoryTracer();
        $cup = Cup::of(new SidedDie(6), 3);
        $cup->setTracer($tracer);
        $cup->minimum();
        $tracer->get(-3);
    }
}
