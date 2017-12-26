<?php
/**
 * It's a dice-cup: you put your die in the cup, you shake it and then you get the result.
 * @author Bertrand Andres
 */

namespace Ethtezahl\DiceRoller;

interface Rollable
{
    /**
     * Returns the rollable minimun result.
     *
     * MUST be lesser than or equal to the maximum value
     *
     * @return int
     */
    public function getMinimum(): int;

    /**
     * Returns the rollable maximum result.
     *
     * MUST be greater than or equal to the minimum value
     *
     * @return int
     */
    public function getMaximum(): int;

    /**
     * Returns the result of a roll.
     *
     * @return int
     */
    public function roll(): int;

    /**
     * Returns information about how the Rollable has executed the last roll.
     *
     * If no roll was performed this method MUST return an empty string
     *
     * @return string
     */
    public function explain(): string;

    /**
     * Returns the string representation of the
     * Rollable object using Dice annotation.
     *
     * @return string
     */
    public function __toString();
}
