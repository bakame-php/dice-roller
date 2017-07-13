<?php
namespace Ethtezahl\DiceRoller;

use OutOfRangeException;

final class Dice
{
    /**
     *
     * @var int
     */
    private $sides;

    /**
     * @param int number of sides of your dice.
     * @throws OutOfRangeException if param given is 1 or less
     */
    public function __construct(int $pSides)
    {
        if (1 >= $pSides) {
            throw new OutOfRangeException('Your dice must have at least 2 sides.');
        }

        $this->sides = $pSides;
    }

    /**
     * You roll the dice and get the result.
     * @return int
     */
    public function roll() : int
    {
        return mt_rand(1, $this->sides);
    }
}
