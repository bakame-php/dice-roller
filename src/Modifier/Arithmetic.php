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

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Exception;
use Bakame\DiceRoller\Rollable;

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
        $this->method = self::OPERATOR[$operator];
        $this->rollable = $rollable;
        $this->value = $value;
        $this->trace = '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        $str = (string) $this->rollable;
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
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
    public function roll(): int
    {
        $roll = $this->calculate('roll');

        $this->setTrace();

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
    private function setTrace()
    {
        $str = $this->rollable->getTrace();
        if (strpos($str, '+') !== false) {
            $str = '('.$str.')';
        }

        $this->trace = $str.' '.$this->operator.' '.$this->value;
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
