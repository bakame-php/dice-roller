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

use Bakame\DiceRoller\Exception\TooFewSides;
use Countable;
use function random_int;
use function sprintf;

final class Dice implements Countable, Rollable
{
    /**
     * @var int
     */
    private $sides;

    /**
     * new instance.
     *
     * @param int $sides side count
     *
     * @throws TooFewSides if a Dice contains less than 2 sides
     */
    public function __construct(int $sides)
    {
        if (2 > $sides) {
            throw new TooFewSides(sprintf('Your dice must have at least 2 sides, `%s` given.', $sides));
        }

        $this->sides = $sides;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'D'.$this->sides;
    }

    /**
     * Returns the side count.
     *
     * @return int
     */
    public function count()
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimum(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximum(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): int
    {
        return random_int(1, $this->sides);
    }
}
