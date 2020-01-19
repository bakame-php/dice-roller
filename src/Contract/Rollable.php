<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Contract;

interface Rollable extends \JsonSerializable
{
    /**
     * Returns the rollable minimum result.
     *
     * MUST be lesser than or equal to the maximum value
     */
    public function minimum(): int;

    /**
     * Returns the rollable maximum result.
     *
     * MUST be greater than or equal to the minimum value
     */
    public function maximum(): int;

    /**
     * Returns the result of a roll.
     */
    public function roll(): Roll;

    /**
     * Returns the Rollable dice annotation as a string.
     */
    public function notation(): string;
}
