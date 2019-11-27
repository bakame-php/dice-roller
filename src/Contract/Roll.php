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

namespace Bakame\DiceRoller\Contract;

interface Roll
{
    /**
     * The roll result int value.
     *
     */
    public function value(): int;

    /**
     * The operations needed to obtain the resulted value.
     *
     */
    public function operation(): string;

    /**
     * The rollable expression.
     *
     */
    public function expression(): string;

    /**
     * The rollable value as a string.
     *
     */
    public function toString(): string;
}
