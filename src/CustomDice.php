<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Bakame\DiceRoller;

use Countable;

final class CustomDice implements Countable, Rollable
{
    /**
     * @var string
     */
    private $trace;

    /**
     * @var int[]
     */
    private $sideValues = [];

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        return 'D['.implode(',', $this->sideValues).']';
    }

    /**
     * New instance
     *
     * @param int ..$sideValue
     * @param int... $sideValues
     */
    public function __construct(int ...$sideValues)
    {
        if (2 > count($sideValues)) {
            throw new Exception(sprintf('Your dice must have at least 2 sides, `%s` given.', count($sideValues)));
        }

        $this->trace = '';
        $this->sideValues = $sideValues;
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return count($this->sideValues);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return min($this->sideValues);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return max($this->sideValues);
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
        $index = random_int(1, count($this->sideValues) - 1);
        $roll  = $this->sideValues[$index];
        $this->trace = (string) $roll;

        return $roll;
    }
}
