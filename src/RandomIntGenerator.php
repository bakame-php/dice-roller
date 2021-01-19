<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

interface RandomIntGenerator
{
    /**
     * Returns a random integer in the range min to max, inclusive.
     *
     * @throws \Bakame\DiceRoller\IntGenerationFailed
     */
    public function generateInt(int $minimum, int $maximum): int;
}