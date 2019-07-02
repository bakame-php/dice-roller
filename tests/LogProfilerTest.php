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
use Bakame\DiceRoller\LogProfiler;
use Bakame\DiceRoller\MemoryLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \Bakame\DiceRoller\LogProfiler
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
     * @covers \Bakame\DiceRoller\MemoryLogger
     * @covers ::addTrace
     */
    public function testDiceRollerLogger(): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $rollable = Cup::fromRollable(new SidedDie(6), 3);
        $rollable->setProfiler(new LogProfiler($this->logger, 'foobar'));
        $rollable->roll();
        self::assertCount(1, $this->logger->getLogs());
        $this->logger->clear('foobar');
        self::assertCount(0, $this->logger->getLogs('foobar'));
        self::assertCount(0, $this->logger->getLogs());
    }
}
