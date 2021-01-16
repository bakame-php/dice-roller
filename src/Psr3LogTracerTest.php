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

/**
 * @coversDefaultClass \Bakame\DiceRoller\Psr3LogTracer
 */
final class Psr3LogTracerTest extends TestCase
{
    private Psr3Logger $logger;

    private Psr3LogTracer $tracer;

    protected function setUp(): void
    {
        $this->logger = new Psr3Logger();
        $this->tracer = new Psr3LogTracer($this->logger);
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
        self::assertSame(Psr3LogTracer::DEFAULT_LOG_FORMAT, $this->tracer->logFormat());
    }

    /**
     * @covers ::append
     * @dataProvider provideLogLevel
     */
    public function testAppendTracing(string $logLevel): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $this->logger->log($logLevel, 'hello {world}', ['world' => 'monde']);

        $tracer = new Psr3LogTracer($this->logger, $logLevel);
        $rollable = Cup::of(3, new SidedDie(6));
        $rollable->setTracer($tracer);
        $rollable->roll();

        self::assertCount(1, $this->logger->getLogs());
        $this->logger->clear($logLevel);
        self::assertCount(0, $this->logger->getLogs($logLevel));
        self::assertCount(0, $this->logger->getLogs());
    }

    public function provideLogLevel(): iterable
    {
        return [
            ['level' => 'foobar'],
            ['level' => LogLevel::DEBUG],
        ];
    }
}
