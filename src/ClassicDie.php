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
use Bakame\DiceRoller\Exception\UnknownExpression;
use function random_int;
use function sprintf;

final class ClassicDie implements Dice
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
     * @throws TooFewSides if a SimpleDice contains less than 2 sides
     */
    public function __construct(int $sides)
    {
        if (2 > $sides) {
            throw new TooFewSides(sprintf('Your dice must have at least 2 sides, `%s` given.', $sides));
        }

        $this->sides = $sides;
    }

    /**
     * new instance from a string expression.
     *
     * @throws UnknownExpression if the expression is not valid.
     */
    public static function fromString(string $expression): self
    {
        if (1 === preg_match('/^d(?<sides>\d+)$/i', $expression, $matches)) {
            return new self((int) $matches['sides']);
        }

        throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid ', $expression));
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'D'.$this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
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
