<?php

/**
 * This file is part of the League.csv library
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bakame-php/dice-roller/
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
     */
    public function getMinimum(): int;

    /**
     * Returns the rollable maximum result.
     *
     * MUST be greater than or equal to the minimum value
     */
    public function getMaximum(): int;

    /**
     * Returns the result of a roll.
     */
    public function roll(): int;

    /**
     * Returns the string representation of the
     * Rollable object using Dice annotation.
     */
    public function toString(): string;
}
