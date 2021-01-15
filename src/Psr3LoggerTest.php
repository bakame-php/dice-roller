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
 * @coversDefaultClass \Bakame\DiceRoller\Psr3Logger
 */
final class Psr3LoggerTest extends TestCase
{
    private Psr3Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Psr3Logger();
    }

    /**
     * @dataProvider provideLogLevel
     */
    public function testLogger(string $logLevel): void
    {
        $this->logger->clear();
        self::assertCount(0, $this->logger->getLogs());
        $this->logger->log($logLevel, 'hello {world}', ['world' => 'monde']);
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
