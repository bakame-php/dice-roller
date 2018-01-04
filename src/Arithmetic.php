<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Bakame\DiceRoller;

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
     * @var array
     */
    private $stack = [];

    /**
     * @var string
     */
    private $trace;

    /**
     * The method name associated with a given algo
     *
     * @var string
     */
    private $method;

    /**
     * new instance
     *
     * @param Rollable $rollable
     * @param string   $operator
     * @param int      $value
     * @param string   $operator
     */
    public function __construct(Rollable $rollable, string $operator, int $value)
    {
        $this->validate($operator, $value);
        $this->operator = $operator;
        $this->method = self::OPERATOR[$operator];
        $this->rollable = $rollable;
        $this->value = $value;
        $this->trace = '';
        $this->stack = [];
    }

    /**
     * Validate the Modifier settings
     *
     * @param string $operator
     * @param int    $value
     *
     * @throws Exception if the value is lesser than 0
     * @throws Exception if the operator is not recognized
     */
    private function validate(string $operator, int $value)
    {
        if (!isset(self::OPERATOR[$operator])) {
            throw new Exception(sprintf('Invalid or Unsupported operator `%s`', $operator));
        }

        if (0 > $value || (0 === $value && $operator == self::DIVISION)) {
            throw new Exception(sprintf('The submitted value `%i` is invalid for the given `%s` operator', $this->value, $operator));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';
        $this->stack = [];

        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
    }

    /**
     * Returns the inner rollable object.
     *
     * @return Rollable
     */
    public function getRollable(): Rollable
    {
        return $this->rollable;
    }

    /**
     * Returns the arithmetic operator to be used by the modifier.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Returns the value to be used by the modifier.
     *
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): array
    {
        return $this->stack;
    }

    /**
     * {@inheritdoc}
     */
    public function getTraceAsString(): string
    {
        return $this->trace;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $roll = $this->calculate('roll');

        $stack = $this->rollable->getTrace();
        $this->stack = [
            'roll' => (string) $roll,
            'modifier' => $this->operator.' '.$this->value,
            'inner_stack' => $stack,
        ];

        $str = $this->rollable->getTraceAsString();
        if (strpos($str, '+') !== false) {
            $str = '('.$str.')';
        }

        $this->trace = $str.' '.$this->operator.' '.$this->value;

        return $roll;
    }

    /**
     * Computes the sum to be return.
     *
     * @param string $method One of the Rollable method
     *
     * @return int
     */
    private function calculate(string $method): int
    {
        return $this->{$this->method}($method);
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

    /**
     * Return an instance with the specified Rollable object.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified Rollable object.
     *
     * @param Rollable $rollable
     *
     * @return self
     */
    public function withRollable(Rollable $rollable): self
    {
        if ($rollable == $this->rollable) {
            return $this;
        }

        return new self($rollable, $this->operator, $this->value);
    }

    /**
     * Return an instance with the specified operator.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified operator.
     *
     * @param string $operator
     *
     * @return self
     */
    public function withOperator(string $operator): self
    {
        if ($operator === $this->operator) {
            return $this;
        }

        return new self($this->rollable, $operator, $this->value);
    }

    /**
     * Return an instance with the specified value.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified operator.
     *
     * @param int $value
     *
     * @return self
     */
    public function withValue(int $value): self
    {
        if ($value === $this->value) {
            return $this;
        }

        return new self($this->rollable, $this->operator, $value);
    }
}
