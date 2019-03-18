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

namespace Bakame\DiceRoller\Decorator;

use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Profiler\ProfilerAware;
use Bakame\DiceRoller\Rollable;
use Bakame\DiceRoller\RollableDecorator;
use Bakame\DiceRoller\Traceable;
use function abs;
use function intdiv;
use function sprintf;
use function strpos;

final class Arithmetic implements RollableDecorator, Traceable
{
    use ProfilerAware;

    public const ADDITION = '+';
    public const SUBSTRACTION = '-';
    public const DIVISION = '/';
    public const EXPONENTIATION = '^';
    public const MULTIPLICATION = '*';

    private const OPERATOR = [
        self::ADDITION => 1,
        self::SUBSTRACTION => 1,
        self::EXPONENTIATION => 1,
        self::DIVISION => 1,
        self::MULTIPLICATION => 1,
    ];

    /**
     * @var Rollable
     */
    private $rollable;

    /**
     * @var int
     */
    private $value;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string
     */
    private $trace = '';

    /**
     * new instance.
     *
     *
     * @throws UnknownAlgorithm if the operator is not recognized
     * @throws IllegalValue     if the value is invalid for a given operator
     */
    public function __construct(Rollable $rollable, string $operator, int $value)
    {
        if (!isset(self::OPERATOR[$operator])) {
            throw new UnknownAlgorithm(sprintf('Invalid or Unsupported operator `%s`', $operator));
        }

        if (0 > $value || (0 === $value && $operator == self::DIVISION)) {
            throw new IllegalValue(sprintf('The submitted value `%s` is invalid for the given `%s` operator', $value, $operator));
        }

        $this->rollable = $rollable;
        $this->operator = $operator;
        $this->value = $value;
        $this->setProfiler();
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerRollable(): Rollable
    {
        return $this->rollable;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        $str = $this->rollable->toString();
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $value = $this->rollable->roll();
        $retval = $this->calculate($value);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->setTrace($value));

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $value = $this->rollable->getMinimum();
        $retval = $this->calculate($value);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->setTrace($value));

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $value = $this->rollable->getMaximum();
        $retval = $this->calculate($value);

        $this->profiler->addTrace($this, __METHOD__, $retval, $this->setTrace($value));

        return $retval;
    }

    /**
     * Computes the value to be return.
     */
    private function calculate(int $value): int
    {
        if ('+' === $this->operator) {
            return $value + $this->value;
        }

        if ('-' === $this->operator) {
            return $value - $this->value;
        }

        if ('*' === $this->operator) {
            return $value * $this->value;
        }

        if ('/' === $this->operator) {
            return intdiv($value, $this->value);
        }

        if ($value > -1) {
            return $value ** $this->value;
        }

        return (int) (abs($value) ** $this->value) * -1;
    }

    /**
     * Format the trace as string.
     */
    private function setTrace(int $value): string
    {
        $this->trace = $value.' '.$this->operator.' '.$this->value;

        return $this->trace;
    }
}
