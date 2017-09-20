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
    private $size;

    /**
     * new instance
     *
     * @param int $pSize side count
     *
     * @throws Exception if a Dice contains less than 2 sides
     */
    public function __construct(int $pSize)
    {
        if (2 > $pSize) {
            throw new Exception(sprintf('Your dice must have at least 2 sides, `%s` given.', $pSize));
        }

        $this->size = $pSize;
    }

    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function roll() : int
    {
        return random_int(1, $this->size);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return 'D'.$this->size;
    }
}
