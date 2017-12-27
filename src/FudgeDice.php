<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Bakame\DiceRoller;

use Countable;

final class FudgeDice implements Countable, Rollable
{
    /**
     * @var string
     */
    private $trace;

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        return 'DF';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return 1;
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
        $res = random_int(-1, 1);
        $this->trace = (string) $res;

        return $res;
    }
}
