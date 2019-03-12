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

use Bakame\DiceRoller\Profiler\Logger;
use Bakame\DiceRoller\Profiler\Profiler;
use Bakame\DiceRoller\Test\Bakame;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Profiler\Profiler
 */
final class ProfilerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Profiler
     */
    private $profiler;

    protected function setUp(): void
    {
        $this->logger = new Logger();
        $this->profiler = new Profiler($this->logger);
    }

    public function testLogger(): void
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
}
