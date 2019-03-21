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

use Bakame\DiceRoller\Exception\UnknownAlgorithm;
use Bakame\DiceRoller\Exception\UnknownExpression;

interface Parser
{

    /**
     * Extract pool expressions from a generic string expression.
     *
     * @return string[]
     */
    public function extractPool(string $expression): array;

    /**
     * Returns an array representation of a Pool.
     *
     *  - If the string is the empty string a empty array is returned
     *  - Otherwise an array containing:
     *         - the pool definition
     *         - the pool modifiers
     *
     * @throws UnknownExpression
     * @throws UnknownAlgorithm
     */
    public function parsePool(string $expression): array;
}
