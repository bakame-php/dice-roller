<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Countable;

final class PercentileDice implements Countable, Rollable
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

        return 'D%';
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        $this->trace = '';

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        $this->trace = '';

        return 100;
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
        $res = random_int(1, 100);
        $this->trace = (string) $res;

        return $res;
    }
}
