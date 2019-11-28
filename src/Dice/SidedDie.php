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

namespace Bakame\DiceRoller\Dice;

use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Exception\TooFewSides;
use Bakame\DiceRoller\Exception\UnknownExpression;
use Bakame\DiceRoller\Toss;
use function preg_match;
use function random_int;
use function sprintf;

final class SidedDie implements Dice
{
    private const REGEXP_EXPRESSION = '/^d(?<sides>\d+)$/i';
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
     * New instance from a string expression.
     *
     * @throws UnknownExpression if the expression is not valid.
     */
    public static function fromExpression(string $expression): self
    {
        if (1 === preg_match(self::REGEXP_EXPRESSION, $expression, $matches)) {
            return new self((int) $matches['sides']);
        }

        throw new UnknownExpression(sprintf('the submitted dice format `%s` is invalid ', $expression));
    }

    /**
     * {@inheritdoc}
     */
    public function expression(): string
    {
        return 'D'.$this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function minimum(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function maximum(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritdoc}
     */
    public function roll(): Roll
    {
        $result = random_int(1, $this->sides);

        return new Toss($result, (string) $result);
    }
}
