<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */
declare(strict_types=1);

namespace Ethtezahl\DiceRoller;

use Countable;

final class FudgeDice implements Countable, Rollable
{
    /**
     * Returns the side count
     *
     * @return int
     */
    public function count()
    {
        return 3;
    }

    /**
     * @inheritdoc
     */
    public function getMinimum(): int
    {
        return -1;
    }

    /**
     * @inheritdoc
     */
    public function getMaximum(): int
    {
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function roll(): int
    {
        return random_int(-1, 1);
    }
}
