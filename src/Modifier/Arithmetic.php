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

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Profiler;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\Traceable;
use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\LogProfiler;
use function abs;
use function intdiv;
use function sprintf;
use function strpos;

final class Arithmetic implements Modifier, Traceable
{
    public const ADD = '+';
    public const SUB = '-';
    public const DIV = '/';
    public const EXP = '^';
    public const MUL = '*';

    private const OPERATOR = [
        self::ADD => 1,
        self::SUB => 1,
        self::EXP => 1,
        self::DIV => 1,
        self::MUL => 1,
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
     * @var Profiler
     */
    private $profiler;

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

        if (0 > $value || (0 === $value && $operator == self::DIV)) {
            throw new IllegalValue(sprintf('The submitted value `%s` is invalid for the given `%s` operator', $value, $operator));
        }

        $this->rollable = $rollable;
        $this->operator = $operator;
        $this->value = $value;
        $this->setProfiler(LogProfiler::fromNullLogger());
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
    public function setProfiler(Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfiler(): Profiler
    {
        return $this->profiler;
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

        return $this->decorate($value, __METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $value = $this->rollable->getMinimum();

        return $this->decorate($value, __METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $value = $this->rollable->getMaximum();

        return $this->decorate($value, __METHOD__);
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(int $value, string $method): int
    {
        $retval = $this->calculate($value);

        $this->trace = $value.' '.$this->operator.' '.$this->value;
        $this->profiler->addTrace($this, $method, $retval, $this->trace);

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
}
