<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test\Tracer;

use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\Dice;
use Bakame\DiceRoller\Test\Bakame;
use Bakame\DiceRoller\Tracer\Logger;
use Bakame\DiceRoller\Tracer\LogTracer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Tracer\LogTracer
 */
final class LogTracerTest extends TestCase
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LogTracer
     */
    private $profiler;

    protected function setUp(): void
    {
        $this->logger = new Logger();
        $this->profiler = new LogTracer($this->logger);
    }

    public function testLoggerAccesor(): void
    {
        self::assertSame($this->logger, $this->profiler->getLogger());
        $this->profiler->setLogger(new Logger());
        self::assertNotSame($this->logger, $this->profiler->getLogger());
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
     * @covers \Bakame\DiceRoller\Tracer\Logger
     */
    public function testDiceRollerLogger(): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $rollable = Cup::createFromRollable(3, new Dice(6), $this->profiler);
        $rollable->roll();
        self::assertCount(1, $this->logger->getLogs());
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
    }
}
