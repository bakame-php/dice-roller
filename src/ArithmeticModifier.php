<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

final class ArithmeticModifier implements Rollable
{
    /**
     * The rollable object to decorate
     *
     * @var Rollable
     */
    private $rollable;

    /**
     * The value to use
     *
     * @var int
     */
    private $value;

    /**
     * The operator
     *
     * @var string
     */
    private $operator;

    /**
     * new Instance
     *
     * @param Rollable $pRollable
     * @param int      $pValue
     * @param string   $operator
     *
     * @throws Exception if the value is lesser than 0
     * @throws Exception if the operator is not recognized
     */
    public function __construct(Rollable $pRollable, int $pValue, string $pOperator)
    {
        if ($pValue < 0) {
            throw new Exception(sprintf('The submitted value `%s` MUST be equal or greather than 0', $pValue));
        }

        if (!in_array($pOperator, ['+', '-', '*', '^', '/'])) {
            throw new Exception(sprintf('Invalid or Unsupported operator `%s`', $pOperator));
        }

        $this->operator = $pOperator;
        $this->rollable = $pRollable;
        $this->value = $pValue;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return $this->calculate('getMinimum');
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return $this->calculate('getMaximum');
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return $this->calculate('roll');
    }

    /**
     * Compute the sum to be return
     *
     * @param string $pMethod One of the Rollable method
     *
     * @return int
     */
    private function calculate(string $pMethod): int
    {
        if ('+' == $this->operator) {
            return $this->rollable->$pMethod() + $this->value;
        }

        if ('-' == $this->operator) {
            return $this->rollable->$pMethod() - $this->value;
        }

        if ('*' == $this->operator) {
            return $this->rollable->$pMethod() * $this->value;
        }

        if ('/' == $this->operator) {
            return intdiv($this->rollable->$pMethod(), $this->value);
        }

        return (int) $this->rollable->$pMethod() ** $this->value;
    }
}
