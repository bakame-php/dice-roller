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

use Countable;
use IteratorAggregate;

interface Pool extends IteratorAggregate, Countable, Rollable
{
    /**
     * Tells whether the Pool contains or not some Rollable object.
     */
    public function isEmpty(): bool;
}
