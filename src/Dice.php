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
    private $explain;

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

        $this->sides = $sides;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'D'.$this->sides;
    }

    /**
     * Returns the side count.
     *
     * @return int
     */
    public function count()
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $this->explain = random_int(1, $this->sides);

        return $this->explain;
    }

    /**
     * {@inheritdoc}
     */
    public function explain(): string
    {
        return (string) $this->explain;
    }
}
