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

interface Roll extends \JsonSerializable
{
    /**
     * The roll value as in integer.
     */
    public function value(): int;

    /**
     * The executed operation to obtain the roll value.
     */
    public function operation(): string;

    /**
     * The roll context.
     */
    public function context(): ?Context;

    /**
     * The roll value as a string.
     */
    public function asString(): string;

    /**
     * The roll data presented as an array.
     */
    public function info(): array;
}
