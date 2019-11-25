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

use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Trace;
use Bakame\DiceRoller\Contract\Tracer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ReflectionClass;
use function array_search;

final class TraceLog implements Tracer
{
    public const DEFAULT_LOG_FORMAT = '[{source}] - {subject} : {operation} = {result}';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $logLevel;

    /**
     * @var string
     */
    private $logFormat;

    /**
     * New instance.
     */
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

    /**
     * {@inheritDoc}
     */
    public function createTrace(string $source, Rollable $subject, string $operation, int $result, array $optionals = []): Trace
    {
        return new TraceEntry($source, $subject, $operation, $result, $optionals);
    }

    /**
     * {@inheritDoc}
     */
    public function addTrace(Trace $trace): void
    {
        static $psr3logLevels = null;

        $psr3logLevels = $psr3logLevels ?? (new ReflectionClass(LogLevel::class))->getConstants();

        if (false !== array_search($this->logLevel, $psr3logLevels, true)) {
            $this->logger->{$this->logLevel}($this->logFormat, $trace->context());

            return;
        }

        $this->logger->log($this->logLevel, $this->logFormat, $trace->context());
    }

    /**
     * Returns the underlying LoggerInterface object.
     */
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
