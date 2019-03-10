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

use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;

final class Arithmetic implements Rollable
{
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
     * @var Profiler|null
     */
    private $profiler;

    /**
     * new instance.
     *
     * @param  ?Profiler        $profiler
     * @throws UnknownAlgorithm if the operator is not recognized
     * @throws IllegalValue     if the value is invalid for a given operator
     */
    public function __construct(Rollable $rollable, string $operator, int $value, ?Profiler $profiler = null)
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
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
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
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($value), $retval);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $value = $this->rollable->getMinimum();

        $retval = $this->calculate($value);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($value), $retval);

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $value = $this->rollable->getMaximum();

        $retval = $this->calculate($value);
        if (null === $this->profiler) {
            return $retval;
        }

        $this->profiler->profile(__METHOD__, $this, $this->setTrace($value), $retval);

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
        return $value.' '.$this->operator.' '.$this->value;
    }
}
