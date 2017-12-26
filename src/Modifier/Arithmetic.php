<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller\Modifier;

use Ethtezahl\DiceRoller\Exception;
use Ethtezahl\DiceRoller\Rollable;

final class Arithmetic implements Rollable
{
    const ADDITION = '+';
    const SUBSTRACTION = '-';
    const DIVISION = '/';
    const EXPONENTIATION = '^';
    const MULTIPLICATION = '*';

    const OPERATOR = [
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
     * @var string
     */
    private $explain;

    /**
     * new instance
     *
     * @param Rollable $rollable
     * @param string   $operator
     * @param int      $value
     * @param string   $operator
     *
     * @throws Exception if the value is lesser than 0
     * @throws Exception if the operator is not recognized
     */
    public function __construct(Rollable $rollable, string $operator, int $value)
    {
        if (!isset(self::OPERATOR[$operator])) {
            throw new Exception(sprintf('Invalid or Unsupported operator `%s`', $operator));
        }

        if (0 > $value || (0 === $value && $operator == self::DIVISION)) {
            throw new Exception(sprintf('The submitted value `%i` is invalid for the given `%s` operator', $value, $operator));
        }

        $this->operator = $operator;
        $this->rollable = $rollable;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function explain(): string
    {
        return (string) $this->explain;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $roll = $this->calculate('roll');

        $this->setExplain();

        return $roll;
    }

    /**
     * Compute the sum to be return.
     *
     * @param string $method One of the Rollable method
     *
     * @return int
     */
    private function calculate(string $method): int
    {
        return $this->{self::OPERATOR[$this->operator]}($method);
    }

    /**
     * Adds a fixed value to a the result from a Rollable public method
     *
     * @param string $method
     */
    private function add(string $method): int
    {
        return $this->rollable->$method() + $this->value;
    }

    /**
     * Substracts a fixed value to a the result from a Rollable public method
     *
     * @param string $method
     */
    private function subs(string $method): int
    {
        return $this->rollable->$method() - $this->value;
    }

    /**
     * Multiplies a fixed value to a the result from a Rollable public method
     *
     * @param string $method
     */
    private function multiply(string $method): int
    {
        return $this->rollable->$method() * $this->value;
    }

    /**
     * divises a fixed value to a the result from a Rollable public method
     *
     * @param string $method
     */
    private function div(string $method): int
    {
        return intdiv($this->rollable->$method(), $this->value);
    }

    /**
     * Exponents a fixed value to a the result from a Rollable public method
     *
     * @param string $method
     */
    private function exp(string $method): int
    {
        $roll = $this->rollable->$method();
        if ($roll > -1) {
            return $roll ** $this->value;
        }

        return (abs($roll) ** $this->value) * -1;
    }

    /**
     * {@inheritdoc}
     */
    private function setExplain()
    {
        $str = $this->rollable->explain();
        if (strpos($str, '+') !== false) {
            $str = '('.$str.')';
        }

        $this->explain = $str.' '.$this->operator.' '.$this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return $this->calculate('getMinimum');
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return $this->calculate('getMaximum');
    }
}
