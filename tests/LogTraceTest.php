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

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Contract\Trace;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice\CustomDie;
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\LogProfiler;
use Bakame\DiceRoller\LogTrace;
use Bakame\DiceRoller\MemoryLogger;
use Bakame\DiceRoller\Modifier\Explode;
use PHPUnit\Framework\TestCase;
use function get_class;

class LogTraceTest extends TestCase
{
    /**
     * @var LogProfiler
     */
    private $profiler;

    public function setUp(): void
    {
        parent::setUp();
        $this->profiler = new LogProfiler(new MemoryLogger());
    }

    public function testItCanBeInstantiated(): void
    {
        $rollable = Cup::fromRollable(new SidedDie(6), 4);
        $rollable->setProfiler($this->profiler);
        $roll = $rollable->roll();
        $trace = $rollable->lastTrace();
        self::assertInstanceOf(Trace::class, $trace);
        self::assertInstanceOf(LogTrace::class, $trace);
        self::assertSame($rollable, $trace->subject());
        self::assertSame($roll, $trace->result());
        self::assertSame(get_class($rollable).'::roll', $trace->source());
        self::assertEmpty($trace->optionals());
        self::assertStringContainsString('+', $trace->line());
        $expectedContext = [
            'source' => get_class($rollable).'::roll',
            'subject' => $trace->subject()->toString(),
            'result' => $trace->result(),
            'line' => $trace->line(),
        ];

        self::assertSame($expectedContext, $trace->context());
    }

    public function testTraceCanHaveOptionalsValue(): void
    {
        $rollable = new Explode(new CustomDie(-1, -1, -2), Explode::EQ, -1);
        $rollable->setProfiler($this->profiler);
        $rollable->roll();
        $trace = $rollable->lastTrace();
        self::assertInstanceOf(LogTrace::class, $trace);
        self::assertArrayHasKey('totalRollsCount', $trace->context());
        self::assertIsInt($trace->context()['totalRollsCount']);
    }
}
