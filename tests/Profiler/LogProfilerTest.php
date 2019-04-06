<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Profiler;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Profiler\LogProfiler;
use Bakame\DiceRoller\Profiler\MemoryLogger;
use Bakame\DiceRoller\SidedDie;
use Bakame\DiceRoller\Test\Bakame;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Profiler\LogProfiler
 */
final class LogProfilerTest extends TestCase
{
    /**
     * @var MemoryLogger
     */
    private $logger;

    /**
     * @var LogProfiler
     */
    private $profiler;

    protected function setUp(): void
    {
        $this->logger = new MemoryLogger();
        $this->profiler = new LogProfiler($this->logger);
    }

    public function testLoggerAccesor(): void
    {
        self::assertSame($this->logger, $this->profiler->getLogger());
    }

    public function testLogLevel(): void
    {
        self::assertSame(LogLevel::DEBUG, $this->profiler->getLogLevel());
        $this->profiler->setLogLevel(LogLevel::INFO);
        self::assertSame(LogLevel::INFO, $this->profiler->getLogLevel());
    }

    public function testLogFormat(): void
    {
        $format = '[{method}] - {rollable} : {trace} = {result}';
        self::assertSame($format, $this->profiler->getLogFormat());

        $format = '{trace} -> {result}';
        $this->profiler->setLogFormat($format);
        self::assertSame($format, $this->profiler->getLogFormat());
    }

    /**
     * @covers \Bakame\DiceRoller\Profiler\MemoryLogger
     */
    public function testDiceRollerLogger(): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $rollable = Cup::createFromRollable(new SidedDie(6), 3);
        $rollable->setProfiler(new LogProfiler($this->logger));
        $rollable->roll();
        self::assertCount(1, $this->logger->getLogs());
        $this->logger->clear(LogLevel::DEBUG);
        self::assertCount(0, $this->logger->getLogs(LogLevel::DEBUG));
        self::assertCount(0, $this->logger->getLogs());
    }
}
