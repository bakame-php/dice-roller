<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Countable;

final class Dice implements Countable, Rollable
{
    /**
     *
     * @var int
     */
    private $sides;

    /**
     * @var string
     */
    private $trace;

    /**
     * new instance
     *
     * @param int $sides side count
     *
     * @throws Exception if a Dice contains less than 2 sides
     */
    public function __construct(int $sides)
    {
        if (2 > $sides) {
            throw new Exception(sprintf('Your dice must have at least 2 sides, `%s` given.', $sides));
        }

        $this->trace = '';
        $this->sides = $sides;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->trace = '';

        return 'D'.$this->sides;
    }

    /**
     * Returns the side count.
     *
     * @return int
     */
    public function count()
    {
        $this->trace = '';

        return $this->sides;
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

        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $roll = random_int(1, $this->sides);
        $this->trace = (string) $roll;

        return $roll;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrace(): string
    {
        return $this->trace;
    }
}
