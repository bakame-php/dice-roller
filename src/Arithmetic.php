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
        self::ADDITION => 'add',
        self::SUBSTRACTION => 'subs',
        self::EXPONENTIATION => 'exp',
        self::DIVISION => 'div',
        self::MULTIPLICATION => 'multiply',
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
     * new instance.
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

        return $this->calculate($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $value = $this->rollable->getMinimum();

        return $this->calculate($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $value = $this->rollable->getMaximum();

        return $this->calculate($value);
    }

    /**
     * Computes the value to be return.
     */
    private function calculate(int $value): int
    {
        $method = self::OPERATOR[$this->operator];
        if ('add' === $method) {
            return $this->add($value);
        }

        if ('subs' === $method) {
            return $this->subs($value);
        }

        if ('exp' === $method) {
            return $this->exp($value);
        }

        if ('div' === $method) {
            return $this->div($value);
        }

        return $this->multiply($value);
    }

    /**
     * Adds a fixed value to a the result from a Rollable public method.
     */
    private function add(int $value): int
    {
        return $value + $this->value;
    }

    /**
     * Substracts a fixed value to a the result from a Rollable public method.
     */
    private function subs(int $value): int
    {
        return $value - $this->value;
    }

    /**
     * Multiplies a fixed value to a the result from a Rollable public method.
     */
    private function multiply(int $value): int
    {
        return $value * $this->value;
    }

    /**
     * divises a fixed value to a the result from a Rollable public method.
     */
    private function div(int $value): int
    {
        return intdiv($value, $this->value);
    }

    /**
     * Exponents a fixed value to a the result from a Rollable public method.
     */
    private function exp(int $value): int
    {
        if ($value > -1) {
            return $value ** $this->value;
        }

        return (int) (abs($value) ** $this->value) * -1;
    }
}
