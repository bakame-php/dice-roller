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

namespace Bakame\DiceRoller\Tracer;

use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Tracer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Psr3LogTracer implements Tracer
{
    public const DEFAULT_LOG_FORMAT = '[{source}] - {notation} : {operation} = {value}';

    private LoggerInterface $logger;

    private string $logLevel;

    private string $logFormat;

    public function __construct(LoggerInterface $logger, string $logLevel = LogLevel::DEBUG, string $logFormat = self::DEFAULT_LOG_FORMAT)
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->logFormat = $logFormat;
    }

    /**
     * Returns an instance which uses the PSR3 Null Logger.
     */
    public static function fromNullLogger(): self
    {
        return new self(new NullLogger());
    }

    public function append(Roll $roll): void
    {
        $this->logger->log($this->logLevel, $this->logFormat, $roll->info());
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns the LogLevel.
     */
    public function logLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Returns the LogFormat.
     */
    public function logFormat(): string
    {
        return $this->logFormat;
    }
}
