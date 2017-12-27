<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/dice-roller/
* @version 1.0.0
* @package bakame-php/dice-roller
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Bakame\DiceRoller;

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
     * Returns the last roll stack trace.
     *
     * If no roll was performed this method MUST return an empty string
     *
     * @return string
     */
    public function getTrace(): string;

    /**
     * Returns the string representation of the
     * Rollable object using Dice annotation.
     *
     * @return string
     */
    public function __toString();
}
