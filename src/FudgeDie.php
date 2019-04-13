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

use Bakame\DiceRoller\Contract\Dice;
use function random_int;

final class FudgeDie implements Dice
{
    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'DF';
    }

    /**
     * Returns the side count.
     *
     */
    public function size(): int
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function minimum(): int
    {
        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        return random_int(-1, 1);
    }
}
