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

use Bakame\DiceRoller\Exception\UnknownNotation;

interface Parser
{
    /**
     * Extract pool expressions from a generic string expression.
     *
     * @throws UnknownNotation If the dice notation can not be parsed
     */
    public function parse(string $notation): array;
}
