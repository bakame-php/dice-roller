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

use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Contract\Rollable;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ReflectionClass;
use function array_search;

final class LogProfiler implements Profiler
{
    public const DEFAULT_LOG_FORMAT =  '[{method}] - {rollable} : {trace} = {result}';

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
     * LogProfiler constructor.
     *
     * @param ?string $logFormat
     */
    public function __construct(LoggerInterface $logger, string $logLevel = LogLevel::DEBUG, ?string $logFormat = null)
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->logFormat = $logFormat ?? self::DEFAULT_LOG_FORMAT;
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
    public function addTrace(Rollable $rollable, string $method, int $roll, string $trace): void
    {
        static $psr3logLevels = null;

        $psr3logLevels = $psr3logLevels ?? (new ReflectionClass(LogLevel::class))->getConstants();

        $context = [
            'method' => $method,
            'rollable' => $rollable->toString(),
            'trace' => $trace,
            'result' => $roll,
        ];

        if (false !== array_search($this->logLevel, $psr3logLevels, true)) {
            $this->logger->{$this->logLevel}($this->logFormat, $context);

            return;
        }

        $this->logger->log($this->logLevel, $this->logFormat, $context);
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
