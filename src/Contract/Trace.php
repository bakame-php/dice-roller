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

interface Trace
{
    public function subject(): Rollable;

    public function source(): string;

    public function result(): int;

    public function operation(): string;

    public function optionals(): array;

    public function context(): array;
}
