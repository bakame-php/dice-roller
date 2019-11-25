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
use Bakame\DiceRoller\Dice\SidedDie;
use Bakame\DiceRoller\MemoryLogger;
use Bakame\DiceRoller\TraceLog;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\TraceLog
 */
final class TraceLogTest extends TestCase
{
    /**
     * @var MemoryLogger
     */
    private $logger;

    /**
     * @var TraceLog
     */
    private $tracer;

    protected function setUp(): void
    {
        $this->logger = new MemoryLogger();
        $this->tracer = new TraceLog($this->logger);
    }

    public function testLogger(): void
    {
        self::assertSame($this->logger, $this->tracer->logger());
    }

    public function testLogLevel(): void
    {
        self::assertSame(LogLevel::DEBUG, $this->tracer->logLevel());
    }

    public function testLogFormat(): void
    {
        self::assertSame(TraceLog::DEFAULT_LOG_FORMAT, $this->tracer->logFormat());
    }

    /**
     * @covers \Bakame\DiceRoller\MemoryLogger
     * @covers ::createTrace
     * @covers ::addTrace
     */
    public function testDiceRollerLogger(): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $rollable = Cup::fromRollable(new SidedDie(6), 3);
        $rollable->setTracer(new TraceLog($this->logger, 'foobar'));
        $rollable->roll();
        self::assertCount(1, $this->logger->getLogs());
        $this->logger->clear('foobar');
        self::assertCount(0, $this->logger->getLogs('foobar'));
        self::assertCount(0, $this->logger->getLogs());
    }
}