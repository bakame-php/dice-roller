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

declare(strict_types=1);

namespace Bakame\DiceRoller;

use Bakame\DiceRoller\Exception\TooFewSides;
use Countable;

final class CustomDice implements Countable, Rollable
{
    /**
     * @var int[]
     */
    private $values = [];

    /**
     * New instance.
     *
     * @param int ...$values
     */
    public function __construct(int ...$values)
    {
        if (2 > count($values)) {
            throw new TooFewSides(sprintf('Your dice must have at least 2 sides, `%s` given.', count($values)));
        }

        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'D['.implode(',', $this->values).']';
    }

    /**
     * Returns the side count.
     *
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return min($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return max($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        $index = random_int(1, count($this->values) - 1);
        $roll = $this->values[$index];

        return $roll;
    }
}
